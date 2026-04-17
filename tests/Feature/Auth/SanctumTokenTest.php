<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('creates a personal access token with abilities')->skip('Sanctum not yet installed');

test('authenticates API request with valid token')->skip('Sanctum not yet installed');

test('rejects API request with invalid token')->skip('Sanctum not yet installed');

test('enforces token abilities')->skip('Sanctum not yet installed');

test('revokes a token')->skip('Sanctum not yet installed');
