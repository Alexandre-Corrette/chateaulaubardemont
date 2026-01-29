<?php
declare(strict_types=1);

const CONTACT_TO   = 'contact@chateau-laubardemont.com';
const MAIL_FROM    = 'formulaire-contact@chateau-laubardemont.com';

const REDIRECT_OK  = '/contact/?success=1';
const REDIRECT_ERR = '/contact/?error=1';

const DEBUG_LOG = true;

const ALLOWED_HOSTS = [
    'chateau-laubardemont.com',
    'www.chateau-laubardemont.com',
    'preprod.chateau-laubardemont.com',
];