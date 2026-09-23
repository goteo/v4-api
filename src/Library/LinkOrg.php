<?php

namespace App\Library;

enum LinkOrg: string
{
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case GitHub = 'github';
    case LinkedIn = 'linkedin';
    case Identica = 'identica';
    case Unknown = 'unknown';

    private static function domains(): array
    {
        return [
            'instagram.com' => self::Instagram,
            'facebook.com' => self::Facebook,
            'fb.com' => self::Facebook,
            'github.com' => self::GitHub,
            'linkedin.com' => self::LinkedIn,
            'identi.ca' => self::Identica,
        ];
    }

    public static function fromUrl(string $url): self
    {
        $host = \parse_url($url, PHP_URL_HOST) ?? '';
        $host = \join('.', \array_slice(\explode('.', $host), -2));

        foreach (self::domains() as $domain => $network) {
            if ($host === $domain || \str_ends_with($host, '.'.$domain)) {
                return $network;
            }
        }

        return self::Unknown;
    }
}
