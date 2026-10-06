<?php

namespace App\Library;

class Link
{
    /**
     * The complete target URL.
     */
    public string $url;

    /**
     * The link relation type, which serves as an ID for a link that unambiguously describes the semantics of the link.
     *
     * @see https://www.iana.org/assignments/link-relations/link-relations.xhtml
     */
    public ?string $rel;

    /**
     * The known organization behind this link.
     */
    public function getOrg(): LinkOrg
    {
        return LinkOrg::fromUrl($this->url);
    }

    public static function tryFrom(mixed $value): self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (\is_array($value)) {
            $link = new self();
            $link->url = $value['url'];
            $link->rel = $value['rel'];

            return $link;
        }

        $link = new self();
        $link->url = $value;

        return $link;
    }
}
