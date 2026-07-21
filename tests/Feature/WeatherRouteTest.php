<?php

test('guests can visit the weather page', function () {
    $response = $this->get(route('weather'));

    $response->assertOk();
});
