<?php

namespace App\Benzina;

use App\Entity\Project\Collaboration;
use App\Entity\Project\Project;
use App\Entity\Project\ProjectCalendar;
use App\Entity\Project\ProjectDeadline;
use App\Entity\Project\ProjectStatus;
use App\Entity\Project\ProjectVideo;
use App\Entity\Project\Update;
use App\Entity\Territory;
use App\Entity\User\User;
use App\Repository\Project\ProjectRepository;
use App\Service\Project\TerritoryService;
use App\Service\Scout\NonCrawlableUriException;
use App\Service\Scout\ScoutService;
use Doctrine\Common\Collections\ArrayCollection;
use Goteo\Benzina\Pump\ArrayPumpTrait;
use Goteo\Benzina\Pump\PumpInterface;

class ProjectsPump implements PumpInterface
{
    use ArrayPumpTrait;
    use DoctrineLoggablePumpTrait;
    use DatabasePumpTrait;
    use ProjectsPumpTrait;
    use LocalizedPumpTrait;
    use TerritoryPumpTrait;
    use CategorizationPumpTrait;

    public function __construct(
        private ProjectRepository $projectRepository,
        private PumpedUserRepository $userRepository,
        private TerritoryService $territoryService,
        private ScoutService $scoutService,
    ) {}

    public function supports(mixed $sample): bool
    {
        if ($this->hasAllKeys($sample, self::PROJECT_KEYS)) {
            return true;
        }

        return false;
    }

    public function pump(mixed $record, array $context): void
    {
        if (empty($record['name'])) {
            return;
        }

        $status = $this->getProjectStatus($record);
        if (\in_array($status, [ProjectStatus::CampaignReviewRejected])) {
            return;
        }

        $created = new \DateTime($record['created']);
        if (\in_array($status, [ProjectStatus::InDraft]) && $created < new \DateTime('2026-01-01')) {
            return;
        }

        $owner = $this->getProjectOwner($record);
        if ($owner === null) {
            return;
        }

        $project = $this->getProject($record);
        if ($project === null) {
            $project = new Project();
        }

        $project->setSlug($record['id']);
        $project->setTerritory($this->getProjectTerritory($record));
        $project->setOwner($owner);
        $project->setStatus($status);
        $project->setMigrated(true);
        $project->setMigratedId($record['id']);
        $project->setDateCreated($created);
        $project->setDateUpdated(new \DateTime());
        $project->setTranslatableLocale($record['lang']);
        $project->setUpdates(new ArrayCollection($this->getProjectUpdates($project, $context)));
        $project->setCollaborations(new ArrayCollection($this->getProjectCollaborations($project, $context)));

        $video = $this->getProjectVideo($record);
        if ($video !== null) {
            $project->setVideo($video);
            $project->setCover($video->cover);
        }

        $cover = $this->getProjectCover($record, $context);
        if ($cover !== null) {
            $project->setCover($cover);
        }

        $conf = $this->getProjectConf($project, $context);

        $project->setDeadline($this->getProjectDeadline($conf));
        $project->setCalendar($this->getProjectCalendar($record));

        $project->addLocale($record['lang']);
        $project->setTitle($record['name'] ?? '');
        $project->setSubtitle($record['subtitle'] ?? '');
        $project->setDescBrief($record['description']);
        $project->setDescAbout($record['about']);
        $project->setDescGoal($record['motivation']);
        $project->setDescTeam($record['related']);

        $themes = $this->getProjectThemes($record, $context);
        foreach ($themes as $theme) {
            $project->addTheme($theme);
        }

        $this->setPreventFlushAndClear(true);
        $this->persist($project, $context);

        $localizations = $this->getProjectLocalizations($project, $context);

        $this->setPreventFlushAndClear(false);
        $this->localize($project, $localizations, $context, [
            'title' => fn($l) => $l['name'],
            'subtitle' => fn($l) => $l['subtitle'],
            'descBrief' => fn($l) => $l['description'],
            'descAbout' => fn($l) => $l['about'],
            'descGoal' => fn($l) => $l['motivation'],
            'descTeam' => fn($l) => $l['related'],
        ]);
    }

    private function getProject(array $record): ?Project
    {
        return $this->projectRepository->findOneBy(['migratedId' => $record['id']]);
    }

    private function getProjectOwner(array $record): ?User
    {
        return $this->userRepository->findPumped($record['owner']);
    }

    private function getProjectLocalizations(Project $project, array $context): array
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `project_lang` l WHERE l.id = :project'
        );

        $query->execute(['project' => $project->getMigratedId()]);

        return $query->fetchAll();
    }

    private function getProjectCalendar(array $record): ProjectCalendar
    {
        $calendar = new ProjectCalendar();
        $calendar->release = new \DateTimeImmutable($record['published']);
        $calendar->minimum = new \DateTimeImmutable($record['passed'] ?? $record['closed']);
        $calendar->optimum = new \DateTimeImmutable($record['success'] ?? $record['closed']);

        return $calendar;
    }

    private function getProjectStatus(array $record): ProjectStatus
    {
        switch ($record['status']) {
            case 1:
                return ProjectStatus::InDraft;
            case 2:
                return ProjectStatus::InCampaignReview;
            case 3:
                return ProjectStatus::InCampaign;
            case 6:
                return ProjectStatus::CampaignFailed;
            case 4:
            case 5:
                return ProjectStatus::FundingPaid;
            case 0:
            default:
                return ProjectStatus::CampaignReviewRejected;
        }
    }

    private function getProjectTerritory(array $record): Territory
    {
        $address = $record['project_location'];

        if ($address === null) {
            if ($record['address'] !== null) {
                $address = $record['address'];
            }

            if ($record['location'] !== null) {
                $address = $record['location'];
            }

            if ($record['address'] !== null && $record['location'] !== null) {
                $address = \sprintf('%s, %s', $record['address'], $record['location']);
            }
        }

        if ($address === null) {
            return Territory::unknown();
        }

        $cleanAddress = $this->cleanLocation($address, 2);

        if ($cleanAddress === '') {
            return Territory::unknown($address);
        }

        return $this->territoryService->search($cleanAddress);
    }

    private function getProjectVideoSource(array $record): ?string
    {
        $video = \trim($record['video']);
        if ($video !== '' && $record['video'] !== null) {
            return $video;
        }

        $media = \trim($record['media']);
        if ($media !== '' && $record['media'] !== null) {
            return $media;
        }

        return null;
    }

    private function getProjectVideo(array $record): ?ProjectVideo
    {
        $url = $this->getProjectVideoSource($record);

        if ($url === '') {
            return null;
        }

        if (!\str_contains($url, '.')) {
            return null;
        }

        try {
            $info = $this->scoutService->get($url);

            if ($info->image === null) {
                return null;
            }

            return new ProjectVideo($info->url, $info->cover ?? $info->image, $info->image);
        } catch (NonCrawlableUriException $e) {
            return new ProjectVideo($e->getUri());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return Update[]
     */
    private function getProjectUpdates(Project $project, array $context): array
    {
        $updates = [];

        $milestones = $this->getProjectMilestones($project, $context);
        foreach ($milestones as $milestone) {
            $update = new Update();
            $update->setProject($project);
            $update->setTranslatableLocale($project->getLocales()[0]);
            $update->setTitle($milestone['title']);
            $update->setSubtitle('');
            $update->setBody('');
            $update->setDate(new \DateTime($milestone['date']));

            $updates[] = $update;
        }

        $posts = $this->getProjectBlogPosts($project, $context);
        foreach ($posts as $post) {
            if ($post['publish'] == 0) {
                continue;
            }

            $update = new Update();
            $update->setProject($project);
            $update->setTranslatableLocale($project->getLocales()[0]);
            $update->setTitle($post['title']);
            $update->setSubtitle($post['subtitle'] ?? '');
            $update->setBody($post['text'] ?? '');
            $update->setDate(new \DateTime($post['date']));
            $update->setAuthor($project->getOwner());

            $updates[] = $update;
        }

        return $updates;
    }

    private function getProjectMilestones(Project $project, array $context): array
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT p.description AS title, p.image, m.date FROM `milestone` p
                INNER JOIN `project_milestone` m ON m.milestone = p.id
                WHERE m.project = :project
            '
        );

        $query->execute(['project' => $project->getMigratedId()]);

        return $query->fetchAll();
    }

    private function getProjectBlogPosts(Project $project, array $context): array
    {
        $query = $this->getDbConnection($context)->prepare(
            "SELECT * FROM `post` p
                INNER JOIN `blog` b ON b.id = p.blog
                WHERE b.type = 'project'
                    AND b.owner = :project
            "
        );

        $query->execute(['project' => $project->getMigratedId()]);

        return $query->fetchAll();
    }

    private function getProjectConf(Project $project, array $context): array
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `project_conf` WHERE `project` = :project'
        );

        $query->execute(['project' => $project->getMigratedId()]);

        $conf = $query->fetch();

        if (!\is_array($conf)) {
            return [
                'one_round' => 0,
                'days_round1' => 40,
                'days_round2' => 40,
            ];
        }

        return $conf;
    }

    private function getProjectDeadline(array $conf): ProjectDeadline
    {
        if ($conf['one_round'] < 1) {
            return ProjectDeadline::Optimum;
        }

        return ProjectDeadline::Minimum;
    }

    private function getImageSource(array $record, array $context): ?string
    {
        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `project_image` WHERE `project` = :project ORDER BY `order` ASC'
        );

        $query->execute(['project' => $record['id']]);

        $images = $query->fetchAll();

        foreach ($images as $image) {
            if ($image['section'] === 'play-video') {
                return $image['image'];
            }
        }

        return $record['image'];
    }

    private function getProjectCover(array $record, array $context): ?string
    {
        $image = $this->getImageSource($record, $context);

        if ($image === null || $image === '') {
            return null;
        }

        if (!\str_contains($image, '.')) {
            return null;
        }

        return \sprintf('https://s3.eu-west-1.amazonaws.com/goteoassets.org/images/%s', $image);
    }

    private function getProjectCollaborations(Project $project, array $context): array
    {
        $collaborations = [];

        $query = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `support` s WHERE s.project = :project'
        );

        $query->execute(['project' => $project->getMigratedId()]);
        $supports = $query->fetchAll();

        foreach ($supports as $support) {
            $fulfilled = in_array($project->getStatus(), [
                ProjectStatus::FundingPaid,
            ]);

            $collaboration = new Collaboration();
            $collaboration->setTitle($support['support']);
            $collaboration->setDescription($support['description']);
            $collaboration->setFulfilled($fulfilled);

            $collaborations[] = $collaboration;
            $project->addCollaboration($collaboration);
        }

        return $collaborations;
    }

    private function getProjectThemes(array $record, array $context): array
    {
        $scQuery = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `social_commitment` sc WHERE sc.id = :scId'
        );

        $scQuery->execute(['scId' => $record['social_commitment']]);
        $socialCommitment = $scQuery->fetch();

        $catsQuery = $this->getDbConnection($context)->prepare(
            'SELECT * FROM `category` c INNER JOIN `project_category` pc ON c.id = pc.category WHERE pc.project = :project'
        );

        $catsQuery->execute(['project' => $record['id']]);
        $categories = $catsQuery->fetchAll();

        return $this->mapOldCategoriesToThemes($socialCommitment['name'] ?? '', $categories);
    }
}
