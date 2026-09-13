<?php

test('welcome page is displayed', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertViewIs('welcome')
        ->assertSee('The B2B LinkedIn', false)
        ->assertSee('creator marketplace', false)
        ->assertSee('Book creators', false)
        ->assertSee('Get booked', false)
        ->assertDontSee('Who are you here as?', false);
});
