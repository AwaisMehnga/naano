<?php

test('welcome page is displayed', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertViewIs('welcome');
});
