<?php

declare(strict_types=1);

it('reports healthy status with db, cache and queue checks', function () {
    $response = $this->getJson('/up');

    $response->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('checks.database.ok', true)
        ->assertJsonPath('checks.cache.ok', true)
        ->assertJsonPath('checks.queue.ok', true);
});
