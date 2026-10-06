<?php

namespace App\Service\Scout;

/**
 * Transforms a Vimeo thumbnail URI into an HD URI for a cover.
 */
class VimeoCoverProcessor implements ScoutProcessorInterface
{
    public function supports(ScoutResult $result): bool
    {
        return $result->image !== null && \in_array($result->image->getHost(), [
            'vimeo.com',
            'i.vimeocdn.com',
        ]);
    }

    public function process(ScoutResult $result): ScoutResult
    {
        $noResPath = \preg_replace('/_\d+x\d+$/', '', $result->image->getPath());
        $highResPath = \sprintf('%s_1280x720', $noResPath);

        $result->cover = $result->image->withPath($highResPath);

        return $result;
    }
}
