<?php

namespace App\Entity\Project;

use App\Entity\Accounting\Accounting;
use App\Entity\Accounting\AccountingOwnerInterface;
use App\Entity\Category;
use App\Entity\DateCreatedTrait;
use App\Entity\DateUpdatedTrait;
use App\Entity\LocalizedInterface;
use App\Entity\LocalizedTrait;
use App\Entity\Matchfunding\MatchCallSubmission;
use App\Entity\Matchfunding\MatchCallSubmissionStatus;
use App\Entity\MigratedTrait;
use App\Entity\Territory;
use App\Entity\Theme;
use App\Entity\User\User;
use App\Entity\UserOwnedInterface;
use App\Entity\UserOwnedTrait;
use App\Library\Link;
use App\Mapping\Provider\EntityMapProvider;
use App\Repository\Project\ProjectRepository;
use AutoMapper\Attribute\MapProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[MapProvider(EntityMapProvider::class)]
#[ORM\Index(fields: ['migratedId'])]
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project implements UserOwnedInterface, AccountingOwnerInterface, LocalizedInterface
{
    use LocalizedTrait;
    use MigratedTrait;
    use DateCreatedTrait;
    use DateUpdatedTrait;
    use UserOwnedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 56, unique: true)]
    #[Gedmo\Slug(fields: ['title'])]
    private ?string $slug = null;

    /**
     * Since Projects can be recipients of funding, they are assigned an Accounting when created.
     * A Project's Accounting represents how much money the Project has raised from the community.
     */
    #[ORM\OneToOne(inversedBy: 'project', cascade: ['persist'])]
    private ?Accounting $accounting = null;

    /**
     * The User who created this Project.
     */
    #[ORM\ManyToOne(inversedBy: 'projects', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $owner = null;

    /**
     * The main title for the project.
     */
    #[ORM\Column(length: 255)]
    #[Gedmo\Translatable()]
    private ?string $title = null;

    /**
     * Secondary head-line for the project.
     */
    #[ORM\Column(length: 255)]
    #[Gedmo\Translatable()]
    private ?string $subtitle = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $cover = null;

    #[ORM\Column(enumType: ProjectDeadline::class)]
    private ?ProjectDeadline $deadline = ProjectDeadline::Minimum;

    #[ORM\Embedded(class: ProjectCalendar::class)]
    private ?ProjectCalendar $calendar = null;

    /**
     * @var Collection<int, Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class)]
    private Collection $categories;

    /**
     * Project's territory of interest.
     */
    #[ORM\Embedded(class: Territory::class)]
    private ?Territory $territory;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable()]
    private ?string $descBrief = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable()]
    private ?string $descAbout = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable()]
    private ?string $descGoal = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Gedmo\Translatable()]
    private ?string $descTeam = null;

    /**
     * A video showcasing the Project.
     */
    #[ORM\Embedded(class: ProjectVideo::class)]
    private ?ProjectVideo $video = null;

    /**
     * The status of this Project as it goes through it's life-cycle.
     * Projects have a start and an end, and in the meantime they go through different phases represented under this status.
     */
    #[ORM\Column(type: 'string', enumType: ProjectStatus::class)]
    private ProjectStatus $status = ProjectStatus::InDraft;

    /**
     * @var Collection<int, Reward>
     */
    #[ORM\OneToMany(targetEntity: Reward::class, mappedBy: 'project')]
    private Collection $rewards;

    /**
     * @var Collection<int, MatchCallSubmission>
     */
    #[ORM\OneToMany(mappedBy: 'project', targetEntity: MatchCallSubmission::class)]
    private Collection $matchCallSubmissions;

    /**
     * @var Collection<int, BudgetItem>
     */
    #[ORM\OneToMany(targetEntity: BudgetItem::class, mappedBy: 'project')]
    private Collection $budgetItems;

    /**
     * @var Collection<int, Update>
     */
    #[ORM\OneToMany(targetEntity: Update::class, mappedBy: 'project', cascade: ['persist'])]
    private Collection $updates;

    /**
     * @var Collection<int, Support>
     */
    #[ORM\OneToMany(targetEntity: Support::class, mappedBy: 'project')]
    private Collection $supports;

    /**
     * @var Collection<int, Collaboration>
     */
    #[ORM\OneToMany(targetEntity: Collaboration::class, mappedBy: 'project', cascade: ['persist'])]
    private Collection $collaborations;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'project', cascade: ['persist'])]
    private Collection $reviews;

    /*
     * A list of URLs provided by the Project owner.\
     * e.g: social profiles, project website.
     *
     * @var Link[]
     */
    #[ORM\Column(nullable: true)]
    private ?array $links = null;

    /**
     * @var Collection<int, Theme>
     */
    #[ORM\ManyToMany(targetEntity: Theme::class, cascade: ['persist'])]
    private Collection $themes;

    public function __construct()
    {
        $this->accounting = Accounting::of($this);
        $this->rewards = new ArrayCollection();
        $this->matchCallSubmissions = new ArrayCollection();
        $this->budgetItems = new ArrayCollection();
        $this->updates = new ArrayCollection();
        $this->supports = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->collaborations = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->themes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getAccounting(): ?Accounting
    {
        return $this->accounting;
    }

    public function setAccounting(?Accounting $accounting): static
    {
        $this->accounting = $accounting;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    public function setCover(?string $cover): static
    {
        $this->cover = $cover;

        return $this;
    }

    public function getDeadline(): ?ProjectDeadline
    {
        return $this->deadline;
    }

    public function setDeadline(ProjectDeadline $deadline): static
    {
        $this->deadline = $deadline;

        return $this;
    }

    public function getCalendar(): ?ProjectCalendar
    {
        return $this->calendar;
    }

    public function setCalendar(ProjectCalendar $calendar): static
    {
        $this->calendar = $calendar;

        return $this;
    }

    /**
     * @return Collection<int, Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Category $category): static
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

        return $this;
    }

    public function removeCategory(Category $category): static
    {
        $this->categories->removeElement($category);

        return $this;
    }

    public function getTerritory(): ?Territory
    {
        return $this->territory;
    }

    public function setTerritory(Territory $territory): static
    {
        $this->territory = $territory;

        return $this;
    }

    public function getDescBrief(): ?string
    {
        return $this->descBrief;
    }

    public function setDescBrief(?string $descBrief): static
    {
        $this->descBrief = $descBrief;

        return $this;
    }

    public function getDescAbout(): ?string
    {
        return $this->descAbout;
    }

    public function setDescAbout(?string $descAbout): static
    {
        $this->descAbout = $descAbout;

        return $this;
    }

    public function getDescGoal(): ?string
    {
        return $this->descGoal;
    }

    public function setDescGoal(?string $descGoal): static
    {
        $this->descGoal = $descGoal;

        return $this;
    }

    public function getDescTeam(): ?string
    {
        return $this->descTeam;
    }

    public function setDescTeam(?string $descTeam): static
    {
        $this->descTeam = $descTeam;

        return $this;
    }

    public function getVideo(): ?ProjectVideo
    {
        return $this->video;
    }

    public function setVideo(?ProjectVideo $video): static
    {
        $this->video = $video;

        return $this;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    /**
     * DO NOT call this method unless you know what you are doing:
     * Instead use `ProjectService::transition` or you WILL miss side-effects.
     */
    public function setStatus(ProjectStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, Reward>
     */
    public function getRewards(): Collection
    {
        return $this->rewards;
    }

    public function addReward(Reward $reward): static
    {
        if (!$this->rewards->contains($reward)) {
            $this->rewards->add($reward);
            $reward->setProject($this);
        }

        return $this;
    }

    public function removeReward(Reward $reward): static
    {
        if ($this->rewards->removeElement($reward)) {
            // set the owning side to null (unless already changed)
            if ($reward->getProject() === $this) {
                $reward->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MatchCallSubmission>
     */
    public function getMatchCallSubmissions(): Collection
    {
        return $this->matchCallSubmissions;
    }

    /**
     * @return Collection<int, MatchCallSubmission>
     */
    public function getMatchCallSubmissionsBy(MatchCallSubmissionStatus $status): Collection
    {
        return $this->matchCallSubmissions->filter(fn($s) => $s->getStatus() === $status);
    }

    public function addMatchCallSubmission(MatchCallSubmission $MatchCallSubmission): static
    {
        if (!$this->matchCallSubmissions->contains($MatchCallSubmission)) {
            $this->matchCallSubmissions->add($MatchCallSubmission);
            $MatchCallSubmission->setProject($this);
        }

        return $this;
    }

    public function removeMatchCallSubmission(MatchCallSubmission $MatchCallSubmission): static
    {
        if ($this->matchCallSubmissions->removeElement($MatchCallSubmission)) {
            // set the owning side to null (unless already changed)
            if ($MatchCallSubmission->getProject() === $this) {
                $MatchCallSubmission->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, BudgetItem>
     */
    public function getBudgetItems(): Collection
    {
        return $this->budgetItems;
    }

    public function addBudgetItem(BudgetItem $budgetItem): static
    {
        if (!$this->budgetItems->contains($budgetItem)) {
            $this->budgetItems->add($budgetItem);
            $budgetItem->setProject($this);
        }

        return $this;
    }

    public function removeBudgetItem(BudgetItem $budgetItem): static
    {
        if ($this->budgetItems->removeElement($budgetItem)) {
            // set the owning side to null (unless already changed)
            if ($budgetItem->getProject() === $this) {
                $budgetItem->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Update>
     */
    public function getUpdates(): Collection
    {
        return $this->updates;
    }

    /**
     * @param Collection<int, Update> $updates
     */
    public function setUpdates(Collection $updates): static
    {
        $this->updates = $updates;

        return $this;
    }

    public function addUpdate(Update $update): static
    {
        if (!$this->updates->contains($update)) {
            $this->updates->add($update);
            $update->setProject($this);
        }

        return $this;
    }

    public function removeUpdate(Update $update): static
    {
        if ($this->updates->removeElement($update)) {
            // set the owning side to null (unless already changed)
            if ($update->getProject() === $this) {
                $update->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Support>
     */
    public function getSupports(): Collection
    {
        return $this->supports;
    }

    public function addSupport(Support $support): static
    {
        if (!$this->supports->contains($support)) {
            $this->supports->add($support);
            $support->setProject($this);
        }

        return $this;
    }

    public function removeSupport(Support $support): static
    {
        if ($this->supports->removeElement($support)) {
            // set the owning side to null (unless already changed)
            if ($support->getProject() === $this) {
                $support->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Collaboration>
     */
    public function getCollaborations(): Collection
    {
        return $this->collaborations;
    }

    /**
     * @param Collection<int, Collaboration> $collaborations
     */
    public function setCollaborations(Collection $collaborations): static
    {
        $this->collaborations = $collaborations;

        return $this;
    }

    public function addCollaboration(Collaboration $collaboration): static
    {
        if (!$this->collaborations->contains($collaboration)) {
            $this->collaborations->add($collaboration);
            $collaboration->setProject($this);
        }

        return $this;
    }

    public function removeCollaboration(Collaboration $collaboration): static
    {
        if ($this->collaborations->removeElement($collaboration)) {
            // set the owning side to null (unless already changed)
            if ($collaboration->getProject() === $this) {
                $collaboration->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setProject($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getProject() === $this) {
                $review->setProject(null);
            }
        }

        return $this;
    }

    /*
     * @return Link[]
     */
    public function getLinks(): ?array
    {
        return $this->links;
    }

    /**
     * @param Link[] $links
     */
    public function setLinks(?array $links): static
    {
        $this->links = $links;

        return $this;
    }

    /**
     * @return Collection<int, Theme>
     */
    public function getThemes(): Collection
    {
        return $this->themes;
    }

    public function addTheme(Theme $theme): static
    {
        if (!$this->themes->contains($theme)) {
            $this->themes->add($theme);
        }

        return $this;
    }

    public function removeTheme(Theme $theme): static
    {
        $this->themes->removeElement($theme);

        return $this;
    }
}
