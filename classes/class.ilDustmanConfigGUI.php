<?php declare(strict_types=1);

use ILIAS\DI\UIServices;
use ILIAS\DI\HTTPServices;
use ILIAS\Filesystem\Stream\Streams;
use ILIAS\Data\DateFormat\FormatBuilder;
use ILIAS\HTTP\Response\Sender\ResponseSendingException;

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
     * @var ilDustmanRepository
     */
    protected $repository;
    /**
     * @var ilDustmanConfigForm
     */
    protected $form;

    public function __construct()
    {
        global $DIC;

        $this->ui = $DIC->ui();
        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->plugin = new ilDustmanPlugin();
        $this->repository = new ilDustmanRepository($DIC->database());

        // ilObjComponentSettingsGUI parameters must be kept alive
        // in order for ajax-requests to work.
        $this->ctrl->saveParameterByClass(
            self::class,
            ['cname', 'ctype', 'slot_id', 'pname']
        );

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
     * @throws ResponseSendingException
     */
    protected function searchCategories() : void
    {
        $body = $this->http->request()->getQueryParams();
        $term = $body['term'] ?? '';

        $this->http->saveResponse(
            $this->http
                ->response()
                ->withBody(Streams::ofString(json_encode(
                    $this->repository->getCategoriesByTerm($term) ?? []
                )))
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
        );

        $this->http->sendResponse();
        $this->http->close();
    }

    protected function initConfigForm() : ilDustmanConfigForm
    {
        return new ilDustmanConfigForm(
            $this->plugin,
            $this->repository,
            $this->ui->mainTemplate(),
            $this->http->request(),
            (new FormatBuilder())->day()->slash()->month()->get(),
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
