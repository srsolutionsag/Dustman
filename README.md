Dustman
========
Dustman is a simple Cron plugin which moves old ILIAS courses and groups into the trash. You can decide for the max. age
of a course/group after this time it gets moved automatically to the trash.

### Important Notice
**Use of this software happens solely on your own risk!!**

Please test the software extensively on your test environment.

If you have questions, sr solutions ag can offer paid support services: support@sr.solutions

### Features
* Excluding Methods:
  * Specify some categories who should be excluded.
  * Specify some keyword-tags which should be excluded.
* Get an automated e-mail x days before the object gets moved to the trash.

### Installation
You need ILIAS 6 or 7 to run this plugin.

In order to install the Dustman plugin go into ILIAS root folder and use:

```bash
mkdir -p Customizing/global/plugins/Services/Cron/CronHook
cd Customizing/global/plugins/Services/Cron/CronHook
git clone https://github.com/srsolutionsag/Dustman.git
```

# ILIAS Plugin SLA

We love and live the philosophy of Open Source Software! Most of our developments, which we develop on behalf of customers or in our own work, we make publicly available to all interested parties free of charge at https://github.com/srsolutionsag.

Do you use one of our plugins professionally? Secure the timely availability of this plugin also for future ILIAS versions by signing an SLA. Find out more about this at https://sr.solutions/plugins.

Please note that we only guarantee support and release maintenance for institutions that sign an SLA.
