<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class IntegrationTest extends TestCase
{
    public function test_get_contacts()
    {
        $response = $this->get('/api/contacts');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => true
            ]);
    }


    public function test_conversation_create()
    {
        $response = $this->postJson('/api/conversations', ['title' => 'test integration', 'participants' => [2,3,4]]);

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => true
            ]);
    }



    public function test_get_conversation()
    {
        $response = $this->get('/api/conversations');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => true
            ]);
    }

    public function test_send_message()
    {
      $postData = [
        'content' => 'hello there',
        'type' => 'Text',
        'senderId' => 2
      ];

      $response = $this->postJson('/api/conversations/1/messages', $postData);

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => true
            ]);
    }

    public function test_get_conversation_details()
    {
        $response = $this->get('/api/conversations/1');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => true
            ]);
    }

    public function test_get_message()
    {
        $response = $this->get('/api/conversations/1/messages/1');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => true
            ]);
    }

    public function test_get_conversation_messages()
    {
        $response = $this->get('/api/conversations/1/messages');

        $response
            ->assertStatus(200)
            ->assertJson([
                'status' => 1,
                'data'   => true
            ]);
    }
}
