<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

$additionalColumns = [
    'twitch_thumbnail' => [
        'exclude' => true,
        'label' => 'LLL:EXT:twitch/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.twitch_thumbnail',
        'config' => [
            'type' => 'link',
            'allowedTypes' => ['url'],
            'readOnly' => true,
            'size' => 40,
        ],
        'displayCond' => 'USER:Ayacoo\\Twitch\\Tca\\DisplayCond\\IsTwitch->match',
    ],
];

ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $additionalColumns);
ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_metadata',
    '--div--;LLL:EXT:twitch/Resources/Private/Language/locallang_db.xlf:tab.twitch, twitch_thumbnail'
);
