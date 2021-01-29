# Acquia Site Factory Content Hub Console
Acquia Site Factory Content Hub Console provides a command line tool to execute commands on all sites that belong to an Acquia
Site Factory subscription. 

# Installation
Install the package with the latest version of composer:

    $composer require acquia/acsf-contenthub-console
    $composer install

Note that this package must be installed locally and in the codebase on your remote platform (Acquia Site Factory) in 
order for commands to work.

# Create A Site Factory Platform

In order for this tool to execute commands remotely on your Acquia Site Factory Platform, you would need to first create a 
platform with the following command:

    $./vendor/bin/commoncli pc
    
This command will guide you to create a platform where to execute commands to. Notice that the alias given to this 
platform will be what you will use later to point to when executing commands.

When the command ask you to choose an application, you can select multiple ones separated by commas.    
    
    ./vendor/bin/commoncli pc
    This command will step you through the process of creating a new platform on which to perform common console commands.
    Platform Type:
      [0] SSH
      [1] DDEV
      [2] Acquia Cloud
      [3] Acquia Cloud Multi Site
      [4] Acquia Cloud Site Factory
     > 4
    Name: ACSF Test Platform
    Alias: acsf-test
    Acquia Cloud API Key? (Instructions: https://docs.acquia.com/acquia-cloud/develop/api/auth/) 00000000-0000-0000-0000-000000000000
    Acquia Cloud Secret? 1111111111111111111111111111111111111111111=
    Choose an Application:
      [00000000-bb71-404e-bc64-59ad90bc4774] ACSF Sites - Test 001
      [00000000-54b6-49bd-aa4f-b067ea2bc362] ACSF Sites - Test 002
      [00000000-245c-4a1f-89da-f020b7e03715] ACSF Sites - Test 003
      [00000000-f07d-4c07-84d6-1655ed8eb75b] ACSF Sites - Test 004
      [00000000-0b6d-4544-82aa-d7a368ff97e3] ACSF Sites - Test 005
      [00000000-b5a8-a644-55d6-79c887ede7f8] ACSF Sites - Test 006
      [00000000-544c-487f-a76b-efbce6c6f282] ACSF Sites - Test 007
      [00000000-7877-454b-a4ee-41abc01fe85e] ACSF Sites - Test 008
       > ACSF Sites - Test 001
    Choose an Environment:
      [1822-a3b26367-6beb-4042-a428-0204ba433bd5] dev
      [1820-a3b26367-6beb-4042-a428-0204ba433bd5] prod
      [1821-a3b26367-6beb-4042-a428-0204ba433bd5] test
     > dev
    Acquia Cloud Site Factory Url: https://www.acsf-test-001.acsitefactory.com
    ACSF Username: test.user
    ACSF API Token: 0000000000000000000000000000000000000000
    +-------------------------------+----------------------------------------------+
    | Property                      | Value                                        |
    +-------------------------------+----------------------------------------------+
    | platform.type                 | Acquia Cloud Site Factory                    |
    | platform.name                 | ACSF Test Platform                           |
    | platform.alias                | acsf-test                                    |
    | acquia.cloud.api_key          | 00000000-0000-0000-0000-000000000000         |
    | acquia.cloud.api_secret       | 1111111111111111111111111111111111111111111= |
    | acquia.cloud.application_ids  | 00000000-bb71-404e-bc64-59ad90bc4774         |
    | acquia.cloud.environment.name | 1822-a3b26367-6beb-4042-a428-0204ba433bd5    |
    | acquia.acsf.url               | https://www.acsf-test-001.acsitefactory.com  |
    | acquia.acsf.user              | test.user                            |
    | acquia.acsf.token             | 0000000000000000000000000000000000000000     |
    +-------------------------------+----------------------------------------------+
    Are these config correct? yes
    "We assume that all your sites are using HTTPS."
    <warning>Is this assumption correct?</warning>yes
    Successfully saved.

    
# Usage
The following are some of the commands that are available to you to be used once deployed to Acquia Site Factory:

    ./vendor/bin/commoncli 
    CommonConsole 0.0.1
    
    Usage:
      command [options] [arguments]
    
    Options:
      -h, --help            Display this help message
      -q, --quiet           Do not output any message
      -V, --version         Display this application version
          --ansi            Force ANSI output
          --no-ansi         Disable ANSI output
      -n, --no-interaction  Do not ask any interactive question
          --uri[=URI]       The url from which to mock a request.
          --bare            Prevents output styling.
      -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
    
    Available commands:
      help                               Displays help for a command
      list                               Lists commands
     ace
      ace:cron:list                      [ace-cl] Lists Scheduled Jobs.
      ace:database:backup:create         [ace-dbcr] Creates database backups.
      ace:database:backup:delete         [ace-dbdel] Deletes database backups.
      ace:database:backup:list           [ace-dbl] Lists database backups.
      ace:database:backup:restore        [ace-dbres] Restores database backups.
     ace-multi
      ace-multi:database:backup:create   [ace-dbcrm] Creates database backups for ACE Multi-site environments.
      ace-multi:database:backup:delete   [ace-dbdelm] Deletes database backups for ACE Multi-site environments.
      ace-multi:database:backup:list     [ace-dblm] Lists database backups for ACE Multi-site environments.
      ace-multi:database:backup:restore  [ace-dbresm] Restores database backups for ACE Multisite environments.
     acsf
      acsf:cron:list                     [acsf-cl] List Scheduled Jobs
      acsf:database:backup:create        [acsf-dbc] Creates database backups for each site on the ACSF platform.
      acsf:database:backup:delete        [acsf-dbd] Deletes a database backup of a site in the ACSF platform.
      acsf:database:backup:list          [acsf-dbl] List database backups for ACSF sites.
      acsf:database:backup:restore       [acsf-dbr] Restores database backups for ACSF sites.
     platform
      platform:create                    [pc] Create a new platform on which to execute common console commands.
      platform:delete                    [pdel] Deletes the specified platform.
      platform:describe                  [pd] Obtain more details about a platform.
      platform:list                      [pl] List available platforms.
      platform:sites                     List available sites registered in the platform.

Now that you have a platform, you can execute a command like:

    $./vendor/bin/commoncli acsf:database:backup:create @acsf-test
   
This command will execute a database creation in all sites in the "ACSF Test Platform". 

## Copyright and license

Copyright &copy; 2021 Acquia Inc.

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
