<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    local_alternative_file_system
 * @copyright  2024 Eduardo Kraus {@link http://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use local_alternative_file_system\storages\gcs\gcs_file_system;
use local_alternative_file_system\storages\s3\s3_file_system;

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {

    global $CFG, $PAGE;

    $settings = new admin_settingpage('local_alternative_file_system', get_string('pluginname', 'local_alternative_file_system'));

    $ADMIN->add('localplugins', $settings);

    if (!empty($CFG->alternative_file_system_class)) {
        $settingsdestinos = [
            's3' => 'Amazon S3',
            'space' => 'Digital Ocean Space',
            'gcs' => 'Google Cloud Storage'
        ];
        $settings->add(new admin_setting_configselect(
            'local_alternative_file_system/settings_destino',
            get_string('settings_destino', 'local_alternative_file_system'),
            get_string('settings_destinodesc', 'local_alternative_file_system'),
            '',
            $settingsdestinos
        ));
        $PAGE->requires->js_call_amd('local_alternative_file_system/settings', 'init');

        $config = get_config('local_alternative_file_system');

        $datalang = [
            'url' => "{$CFG->wwwroot}/local/alternative_file_system/move-to-external.php",
            'local' => $settingsdestinos[$config->settings_destino],
        ];

        if ($config->settings_destino == 's3' || $config->settings_destino == 'space') {
            if ($config->settings_destino == 's3') {
                $datalang['ex_region'] = 'us-east-1';
            } else if ($config->settings_destino == 'space') {
                $datalang['ex_region'] = 'nyc1';
            }

            $s3filesystem = new s3_file_system();
            try {
                $s3filesystem->test_config();

                $string = get_string('settings_success', 'local_alternative_file_system');
                $setting = new admin_setting_heading('local_alternative_file_system/header1', '',
                    $PAGE->get_renderer('core')->render(new notification($string, notification::NOTIFY_SUCCESS, false)));
                $settings->add($setting);

                if ($s3filesystem->missing_count()) {
                    $a = [
                        'missing' => $externalfilesystem->missing_count(),
                        'sending' => $externalfilesystem->sending_count(),
                    ];
                    $string = get_string('settings_migrate', 'local_alternative_file_system', $datalang);
                    $setting = new admin_setting_heading('local_alternative_file_system/header2',
                        get_string('migrate_total', 'local_alternative_file_system', $a),
                        $PAGE->get_renderer('core')->render(new notification($string, notification::NOTIFY_INFO, false)));
                    $settings->add($setting);
                }

            } catch (Exception $e) {
                $setting = new admin_setting_heading('local_alternative_file_system/header3',
                    '',
                    $PAGE->get_renderer('core')->render(new notification($e->getMessage(), notification::NOTIFY_ERROR)));
                $settings->add($setting);
            }

            $setting = new admin_setting_configtext(
                'local_alternative_file_system/settings_s3_region',
                get_string('settings_s3_region', 'local_alternative_file_system', $datalang),
                get_string('settings_s3_regiondesc', 'local_alternative_file_system', $datalang),
                '', PARAM_TEXT);
            $settings->add($setting);

            $setting = new admin_setting_configtext(
                'local_alternative_file_system/settings_s3_credentials_key',
                get_string('settings_s3_credentials_key', 'local_alternative_file_system', $datalang),
                get_string('settings_s3_credentials_keydesc', 'local_alternative_file_system', $datalang),
                '', PARAM_TEXT);
            $settings->add($setting);

            $setting = new admin_setting_configtext(
                'local_alternative_file_system/settings_s3_credentials_secret',
                get_string('settings_s3_credentials_secret', 'local_alternative_file_system', $datalang),
                get_string('settings_s3_credentials_secretdesc', 'local_alternative_file_system', $datalang),
                '', PARAM_TEXT);
            $settings->add($setting);

            $setting = new admin_setting_configtext(
                'local_alternative_file_system/settings_s3_bucketname',
                get_string('settings_bucketname', 'local_alternative_file_system', $datalang),
                get_string('settings_bucketnamedesc', 'local_alternative_file_system', $datalang),
                '', PARAM_TEXT);
            $settings->add($setting);

            $setting = new admin_setting_configtext(
                'local_alternative_file_system/settings_path',
                get_string('settings_path', 'local_alternative_file_system', $datalang),
                get_string('settings_pathdesc', 'local_alternative_file_system', $datalang),
                '', PARAM_TEXT);
            $settings->add($setting);
        }
        if ($config->settings_destino == 'gcs') {

            $gcsfilesystem = new gcs_file_system();
            try {
                $gcsfilesystem->test_config();

                $string = get_string('settings_success', 'local_alternative_file_system');
                $setting = new admin_setting_heading('local_alternative_file_system/header1', '',
                    $PAGE->get_renderer('core')->render(new notification($string, notification::NOTIFY_SUCCESS, false)));
                $settings->add($setting);

                if ($gcsfilesystem->missing_count()) {
                    $string = get_string('settings_migrate', 'local_alternative_file_system', $datalang);
                    $setting = new admin_setting_heading('local_alternative_file_system/header2', '',
                        $PAGE->get_renderer('core')->render(new notification($string, notification::NOTIFY_INFO, false)));
                    $settings->add($setting);
                }

            } catch (Exception $e) {
                $setting = new admin_setting_heading('local_alternative_file_system/header3',
                    '',
                    $PAGE->get_renderer('core')->render(new notification($e->getMessage(), notification::NOTIFY_ERROR)));
                $settings->add($setting);
            }

            $setting = new admin_setting_configtextarea(
                'local_alternative_file_system/settings_gcs_keyfile',
                get_string('settings_gcs_keyfile', 'local_alternative_file_system', $datalang),
                get_string('settings_gcs_keyfiledesc', 'local_alternative_file_system', $datalang),
                '', PARAM_TEXT);
            $settings->add($setting);

            $setting = new admin_setting_configtext(
                'local_alternative_file_system/settings_gcs_bucketname',
                get_string('settings_bucketname', 'local_alternative_file_system', $datalang),
                get_string('settings_bucketnamedesc', 'local_alternative_file_system', $datalang),
                '', PARAM_TEXT);
            $settings->add($setting);

            $setting = new admin_setting_configtext(
                'local_alternative_file_system/settings_path',
                get_string('settings_path', 'local_alternative_file_system', $datalang),
                get_string('settings_pathdesc', 'local_alternative_file_system', $datalang),
                '', PARAM_TEXT);
            $settings->add($setting);
        }

    } else {
        $setting = new admin_setting_heading('local_alternative_file_system/header4',
            get_string('instruction_title', 'local_alternative_file_system'),
            get_string('instruction_install', 'local_alternative_file_system'));
        $settings->add($setting);
    }
}
