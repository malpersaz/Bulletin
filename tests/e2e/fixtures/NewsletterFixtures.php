<?php

namespace E2E\Fixtures;

class NewsletterFixtures
{
    public static function getSubscribers(): array
    {
        return [
            [
                'id' => 1,
                'email' => 'editor@bulletin.org',
                'name' => 'Editorial Staff',
                'status' => 'active',
                'unsubscribe_token' => 'a1b2c3d4e5f60718293a4b5c6d7e8f90',
                'ip_address' => '127.0.0.1',
                'subscribed_at' => '2026-08-20 10:00:00',
                'unsubscribed_at' => null,
            ],
            [
                'id' => 2,
                'email' => 'reader.diaspora@example.com',
                'name' => 'Soran Reader',
                'status' => 'active',
                'unsubscribe_token' => '11223344556677889900aabbccddeeff',
                'ip_address' => '192.168.1.10',
                'subscribed_at' => '2026-08-21 14:30:00',
                'unsubscribed_at' => null,
            ],
            [
                'id' => 3,
                'email' => 'unsubscribed.user@example.com',
                'name' => 'Former Reader',
                'status' => 'unsubscribed',
                'unsubscribe_token' => 'ffeeddccbbaa00998877665544332211',
                'ip_address' => '10.0.0.5',
                'subscribed_at' => '2026-08-01 08:00:00',
                'unsubscribed_at' => '2026-08-15 12:00:00',
            ],
        ];
    }
}
