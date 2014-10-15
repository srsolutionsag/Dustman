Dustman
========
Dustman is a simple Cron plugin which moves old ILIAS courses and groups into the trash. You can decide for the max. age
of a course/group after this time it gets moved automatically to the trash.

###Features
* Excluding Methods:
  * Specify some categories who should be excluded.
  * Specify some keyword-tags which should be excluded.
* Get an automated e-mail x days before the object gets moved to the trash.

###Installation
You need ILIAS >= 4.4 to run this plugin.

In order to install the Dustman plugin go into ILIAS root folder and use:

```bash
mkdir -p Customizing/global/plugins/Services/Cron/CronHook
cd Customizing/global/plugins/Services/Cron/CronHook
git clone https://github.com/studer-raimann/Dustman.git
```

###Contact
studer + raimann ag  
Waldeggstrasse 72  
3097 Liebefeld  
Switzerland 

info@studer-raimann.ch  
www.studer-raimann.ch  
