<?php

use ILIAS\DI\UIServices;
use ILIAS\DI\HTTPServices;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\Data\DateFormat\FormatBuilder;

/**
 * Dustman Configuration GUI
 * @author  Oskar Truffer <ot@studer-raimann.ch>
 * @author  Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanConfigGUI extends ilPluginConfigGUI
{
    /**
     * @var UIServices
     */
    protected $ui;
    /**
     * @var ilDBInterface
     */
    protected $db;
    /**
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var HTTPServices
     */
    protected $http;
    /**
     * @var ilDustmanPlugin
     */
    protected $plugin;
    /**
     * @var ilDustmanConfigForm
     */
    protected $form;

    public function __construct()
    {
        global $DIC;

        $this->ui = $DIC->ui();
        $this->db = $DIC->database();
        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->plugin = new ilDustmanPlugin();
        $this->form = $this->initConfigForm();
    }

    /**
     * @param string $cmd
     * @return void
     */
    public function performCommand($cmd) : void
    {
        switch ($cmd) {
            case 'configure':
            case 'searchCategories':
            case 'save':
                $this->$cmd();
                break;

            default:
                throw new LogicException(self::class . " does not implement command '$cmd'");
        }
    }

    public function configure() : void
    {
        $this->form->show();
    }

    public function save() : void
    {
        if ($this->form->save()) {
            ilUtil::sendSuccess($this->plugin->txt('conf_saved'), true);
            $this->ctrl->redirect($this, 'configure');
        }

        $this->form->show();
    }

    /**
     * asynchronous method that delivers categories for a given
     * term in their titles.
     */
    protected function searchCategories() : void
    {
        $body = $this->http->request()->getQueryParams();
        $term = $body['term'] ?? '';
        $term = $this->db->quote("%$term%", 'text');

        $query = "
            SELECT obj.obj_id, obj.title FROM object_data AS obj
		        LEFT JOIN object_translation AS trans ON trans.obj_id = obj.obj_id
		        WHERE obj.type = 'cat' and (obj.title LIKE $term OR trans.title LIKE $term)
		";

        $result = $this->db->fetchAll($this->db->query($query));
        $matches = [];
        foreach ($result as $row) {
            $matches[] = [
                'value' => $row['obj_id'],
                'display' => $row['title'],
                'searchBy' => $row['title'],
            ];
        }

        $this->http->saveResponse(
            $this->http
                ->response()
                ->withBody(Streams::ofString(json_encode($matches)))
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
        );

        $this->http->sendResponse();
        $this->http->close();
    }

    protected function initConfigForm() : ilDustmanConfigForm
    {
        return new ilDustmanConfigForm(
            $this->plugin,
            $this->ui->mainTemplate(),
            $this->http->request(),
            (new FormatBuilder())
                ->day()
                ->slash()
                ->month()
                ->get(),
            $this->ui->factory()->input()->field(),
            $this->ui->factory()->input()->container()->form(),
            $this->ui->renderer(),
            $this->ctrl->getFormActionByClass(
                self::class,
                'save'
            ),
            $this->ctrl->getLinkTargetByClass(
                self::class,
                'searchCategories',
                "",
                true
            )
        );
    }
}
