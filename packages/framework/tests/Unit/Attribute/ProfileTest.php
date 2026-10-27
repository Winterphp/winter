<?php

declare(strict_types=1);

use Winter\Attribute\Profile;

test('les profils se combinent en OU et supportent la négation', function (): void {
    expect((new Profile('dev'))->matches(['dev']))->toBeTrue()
        ->and((new Profile('dev'))->matches(['prod']))->toBeFalse()
        ->and((new Profile('!prod'))->matches(['dev']))->toBeTrue()
        ->and((new Profile('!prod'))->matches(['prod']))->toBeFalse()
        ->and((new Profile(['dev', 'test']))->matches(['test']))->toBeTrue()
        ->and((new Profile('dev,test'))->matches(['test']))->toBeTrue();
});
