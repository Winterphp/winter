<?php

declare(strict_types=1);

namespace Winter\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
final class Profile
{
    /** @var list<string> */
    public readonly array $profiles;

    /**
     * @param string|list<string> $profiles Un ou plusieurs profils, séparés par des virgules si chaîne.
     *                                       Un profil préfixé par "!" est satisfait lorsqu'il est inactif.
     */
    public function __construct(string|array $profiles)
    {
        $list = \is_string($profiles) ? explode(',', $profiles) : $profiles;
        $list = array_map(static fn (string $profile): string => trim($profile), $list);

        $this->profiles = array_values(array_filter($list, static fn (string $profile): bool => $profile !== ''));
    }

    /**
     * @param list<string> $activeProfiles
     */
    public function matches(array $activeProfiles): bool
    {
        foreach ($this->profiles as $profile) {
            $negated = str_starts_with($profile, '!');
            $name = $negated ? substr($profile, 1) : $profile;
            $active = \in_array($name, $activeProfiles, true);

            if ($negated ? !$active : $active) {
                return true;
            }
        }

        return false;
    }
}
