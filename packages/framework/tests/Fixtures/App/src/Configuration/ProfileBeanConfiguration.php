<?php

declare(strict_types=1);

namespace Winter\Tests\Fixtures\App\Configuration;

use Winter\Attribute\Bean;
use Winter\Attribute\Configuration;
use Winter\Attribute\Profile;
use Winter\Tests\Fixtures\App\Service\ProfiledBean;

#[Configuration]
final class ProfileBeanConfiguration
{
    #[Bean]
    #[Profile('test')]
    public function testBean(): ProfiledBean
    {
        return new ProfiledBean('test');
    }

    #[Bean]
    #[Profile('prod')]
    public function prodBean(): ProfiledBean
    {
        return new ProfiledBean('prod');
    }
}
