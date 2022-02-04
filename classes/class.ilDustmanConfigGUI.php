<?php declare(strict_types=1);

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
     * @var ilCtrl
     */
    protected $ctrl;
    /**
     * @var HTTPServices
     */
    protected $http;
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

        $this->ctrl = $DIC->ctrl();
        $this->http = $DIC->http();
        $this->plugin_object = new ilDustmanPlugin();
        $this->repository = new ilDustmanRepository(
            $DIC->database(),
            $DIC->repositoryTree()
        );

        $this->keepComponentSettings();

        $this->form = new ilDustmanConfigForm(
            $this->plugin_object,
            $this->repository,
            $DIC->ui()->mainTemplate(),
            $this->http->request(),
            (new FormatBuilder())->day()->slash()->month()->get(),
            $DIC->refinery(),
            $DIC->ui()->factory()->input()->field(),
            $DIC->ui()->factory()->input()->container()->form(),
            $DIC->ui()->renderer(),
            $this->getFormAction(),
            $this->getAjaxSource()
        );
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

    protected function configure() : void
    {
        $this->form->show();
    }

    protected function save() : void
    {
        if ($this->form->save()) {
            ilUtil::sendSuccess($this->plugin_object->txt('conf_saved'), true);
            $this->ctrl->redirectByClass(self::class, 'configure');
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

    /**
     * in order to generate correct link targets the ilObjComponentSettingsGUI
     * query parameters must be kept alive.
     */
    protected function keepComponentSettings() : void
    {
        $this->ctrl->saveParameterByClass(
            self::class,
            [
                'cname',
                'ctype',
                'slot_id',
                'pname'
            ]
        );
    }

    protected function getAjaxSource() : string
    {
        return $this->ctrl->getLinkTargetByClass(
            self::class,
            'searchCategories',
            "",
            true
        );
    }

    protected function getFormAction() : string
    {
        return $this->ctrl->getFormActionByClass(
            self::class,
            'save'
        );
    }
}
