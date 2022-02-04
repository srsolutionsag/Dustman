<?php declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use ILIAS\UI\Implementation\Component\Input\Field\Factory as FieldFactory;
use ILIAS\UI\Implementation\Component\Input\Container\Form\Factory as FormFactory;
use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\Data\DateFormat\DateFormat;
use ILIAS\Refinery\Custom\Transformation;
use ILIAS\Refinery\Factory as Refinery;
use ILIAS\UI\Renderer;

/**
 * @author Thibeau Fuhrer <thibeau@sr.solutions>
 */
class ilDustmanConfigForm
{
    /**
     * @var ilDustmanPlugin
     */
    protected $plugin;
    /**
     * @var ilDustmanRepository
     */
    protected $repository;
    /**
     * @var ilGlobalTemplateInterface
     */
    protected $global_template;
    /**
     * @var Renderer
     */
    protected $renderer;
    /**
     * @var ServerRequestInterface
     */
    protected $request;
    /**
     * @var DateFormat
     */
    protected $date_format;
    /**
     * @var Refinery
     */
    protected $refinery;
    /**
     * @var FieldFactory
     */
    protected $field_factory;
    /**
     * @var FormFactory
     */
    protected $form_factory;
    /**
     * @var string
     */
    protected $form_action;
    /**
     * @var string
     */
    protected $ajax_source;
    /**
     * @var Form
     */
    protected $form;

    public function __construct(
        ilDustmanPlugin $plugin,
        ilDustmanRepository $repository,
        ilGlobalTemplateInterface $global_template,
        ServerRequestInterface $request,
        DateFormat $date_format,
        Refinery $refinery,
        FieldFactory $field_factory,
        FormFactory $form_factory,
        Renderer $renderer,
        string $form_action,
        string $ajax_source
    ) {
        $this->plugin = $plugin;
        $this->repository = $repository;
        $this->global_template = $global_template;
        $this->renderer = $renderer;
        $this->request = $request;
        $this->date_format = $date_format;
        $this->refinery = $refinery;
        $this->field_factory = $field_factory;
        $this->form_factory = $form_factory;
        $this->form_action = $form_action;
        $this->ajax_source = $ajax_source;
        $this->form = $this->build();
    }

    public function show() : void
    {
        $this->global_template->setContent(
            $this->renderer->render($this->form)
        );
    }

    /**
     * @return bool
     */
    public function save() : bool
    {
        $data = $this->form->withRequest($this->request)->getData();
        if (empty($data)) {
            return false;
        }

        try {
            foreach ($data as $key => $value) {
                $this->repository->getConfigByIdentifier($key)->setValue($value)->save();
            }
        } catch (arException $e) {
            return false;
        }

        return true;
    }

    /**
     * @return Form
     */
    protected function build() : Form
    {
        return $this->form_factory->standard(
            $this->form_action,
            [
                ilDustmanConfig::CNF_FILTER_CATEGORIES => $this->field_factory->tag(
                    $this->plugin->txt(ilDustmanConfig::CNF_FILTER_CATEGORIES),
                    [] // no tags needed, as all tags are user-generated.
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_FILTER_CATEGORIES, [])
                )->withAdditionalOnLoadCode(
                    $this->getTagAjaxSearchClosure()
                ),

                ilDustmanConfig::CNF_FILTER_KEYWORDS => $this->field_factory->tag(
                    $this->plugin->txt(ilDustmanConfig::CNF_FILTER_KEYWORDS),
                    [], // no tags needed, as all tags are user-generated.
                    $this->plugin->txt('keywords_info')
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_FILTER_KEYWORDS, [])
                ),

                ilDustmanConfig::CNF_EXEC_ON_DATES => $this->field_factory->tag(
                    $this->plugin->txt(ilDustmanConfig::CNF_EXEC_ON_DATES),
                    [] // no tags needed, as all tags are user-generated.
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_EXEC_ON_DATES, [])
                )->withAdditionalTransformation(
                    $this->getDateTimeValidationClosure()
                ),

                ilDustmanConfig::CNF_DELETE_COURSES => $this->field_factory->checkbox(
                    $this->plugin->txt(ilDustmanConfig::CNF_DELETE_COURSES)
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_DELETE_COURSES, false)
                ),

                ilDustmanConfig::CNF_DELETE_GROUPS => $this->field_factory->checkbox(
                    $this->plugin->txt(ilDustmanConfig::CNF_DELETE_GROUPS)
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_DELETE_GROUPS, false)
                ),

                ilDustmanConfig::CNF_FILTER_OLDER_THAN => $this->field_factory->numeric(
                    $this->plugin->txt(ilDustmanConfig::CNF_FILTER_OLDER_THAN)
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_FILTER_OLDER_THAN, null)
                ),

                ilDustmanConfig::CNF_REMINDER_IN_DAYS => $this->field_factory->numeric(
                    $this->plugin->txt(ilDustmanConfig::CNF_REMINDER_IN_DAYS)
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_IN_DAYS, null)
                ),

                ilDustmanConfig::CNF_REMINDER_TITLE => $this->field_factory->text(
                    $this->plugin->txt(ilDustmanConfig::CNF_REMINDER_TITLE)
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_TITLE, '')
                ),

                ilDustmanConfig::CNF_REMINDER_CONTENT => $this->field_factory->textarea(
                    $this->plugin->txt(ilDustmanConfig::CNF_REMINDER_CONTENT),
                    $this->plugin->txt('reminder_content_info')
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_CONTENT, '')
                ),

                ilDustmanConfig::CNF_REMINDER_EMAIL => $this->field_factory->text(
                    $this->plugin->txt(ilDustmanConfig::CNF_REMINDER_EMAIL)
                )->withValue(
                    $this->repository->getConfigValueOrDefault(ilDustmanConfig::CNF_REMINDER_EMAIL, '')
                ),
            ]
        );
    }

    /**
     * @return Closure
     */
    protected function getTagAjaxSearchClosure() : Closure
    {
        return function ($id) {
            return "
                        var {$id}_requests = [];
                        let searchCategories = async function (event) {
                            let tag = il.UI.Input.tagInput.getTagifyInstance('$id')
                            let value = event.detail.value;
                            
                            // abort if value has not at least two characters.
                            if (2 <= value.length) { return; }
                            
                            // show the loading animation and hide the suggestions.
                            tag.loading(true);
                            tag.dropdown.hide();
                            
                            // kill the last request before starting a new one.
                            if (0 < {$id}_requests.length) {
                                for (let i = 0; i < {$id}_requests.length; i++) {
                                    {$id}_requests[i].abort();
                                }
                            }
                            
                            // fetch suggestions asynchronously and store the
                            // current request in the array.
                            {$id}_requests.push($.ajax({
                                type: 'GET',
                                url: encodeURI('$this->ajax_source' + '&term=' + value),
                                success: response => {
                                    // update whitelist, hide loading animation and
                                    // show the suggestions.
                                    tag.settings.whitelist = response;
                                    tag.loading(false);
                                    tag.dropdown.show();
                                },
                            }));
                        }
                    
                        $(document).ready(function () {
                            let tag = il.UI.Input.tagInput.getTagifyInstance('$id');
                            
                            // enforceWhitelist will make the whitelist persistent,
                            // previously found objects will therefore stay in it. 
                            tag.settings.enforceWhitelist = true;
                            tag.on('input', searchCategories);
                        });
            ";
        };
    }

    /**
     * @return Transformation
     */
    protected function getDateTimeValidationClosure() : Transformation
    {
        return $this->refinery->custom()->transformation(
            function ($date) : ?string {
                $datetime = DateTimeImmutable::createFromFormat($this->date_format->toString(), $date);
                if (false !== $datetime) {
                    return $datetime->format($this->date_format->toString());
                }

                return null;
            }
        );
    }
}