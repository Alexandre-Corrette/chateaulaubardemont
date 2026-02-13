<?php
declare(strict_types=1);

function buildContactBody(array $data): string {
    return "
    <h2>Nouvelle demande de contact</h2>
    <table style='border-collapse: collapse; width: 100%;'>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Nom</strong></td>
            <td style='padding: 8px; border: 1px solid #ddd;'>{$data['first_name']} {$data['last_name']}</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Email</strong></td>
            <td style='padding: 8px; border: 1px solid #ddd;'>{$data['email']}</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Téléphone</strong></td>
            <td style='padding: 8px; border: 1px solid #ddd;'>{$data['phone']}</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Date souhaitée</strong></td>
            <td style='padding: 8px; border: 1px solid #ddd;'>{$data['date']}</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Événement</strong></td>
            <td style='padding: 8px; border: 1px solid #ddd;'>{$data['reason']}</td>
        </tr>
        <tr>
            <td style='padding: 8px; border: 1px solid #ddd;'><strong>Message</strong></td>
            <td style='padding: 8px; border: 1px solid #ddd;'>{$data['message']}</td>
        </tr>
    </table>
    ";
}