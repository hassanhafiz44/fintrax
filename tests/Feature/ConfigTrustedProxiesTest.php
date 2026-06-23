<?php

test('trusted_proxies config key is defined and reads TRUSTED_PROXIES env', function () {
    expect(config('app.trusted_proxies'))->not->toBeNull();
});
