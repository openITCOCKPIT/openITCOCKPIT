<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\MessagesOtdTable;
use App\Model\Table\SystemsettingsTable;
use App\Model\Table\UsersTable;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\FrozenDate;
use Cake\Mailer\Mailer;
use Cake\ORM\TableRegistry;
use itnovum\openITCOCKPIT\Core\ValueObjects\User;
use itnovum\openITCOCKPIT\Core\Views\BBCodeParser;
use itnovum\openITCOCKPIT\Core\Views\Logo;
use itnovum\openITCOCKPIT\Database\PaginateOMat;
use itnovum\openITCOCKPIT\Filter\GenericFilter;

/**
 * MessagesOtd Controller
 *
 * @property \App\Model\Table\MessagesOtdTable $MessagesOtd
 * @method \App\Model\Entity\MessagesOtd[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class MessagesOtdController extends AppController {
    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index() {
        if (!$this->isAngularJsRequest()) {
            //Only ship HTML Template
            return;
        }
        /** @var  MessagesOtdTable $MessagesOtdTable */
        $MessagesOtdTable = TableRegistry::getTableLocator()->get('MessagesOtd');

        $GenericFilter = new GenericFilter($this->request);
        $GenericFilter->setFilters([
            'like' => [
                'MessagesOtd.title',
                'MessagesOtd.description',
                'MessagesOtd.date'
            ]
        ]);

        $PaginateOMat = new PaginateOMat($this, $this->isScrollRequest(), $GenericFilter->getPage());

        $messagesOtd = $MessagesOtdTable->getMessagesOTDIndex($GenericFilter, $PaginateOMat);

        $this->set('messagesOtd', $messagesOtd);
        $this->viewBuilder()->setOption('serialize', ['messagesOtd']);
    }

    /**
     * View method
     *
     * @param string|null $id Messages Otd id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null) {
        $messagesOtd = $this->MessagesOtd->get($id, contain: ['Users']);

        $this->set(compact('messagesOtd'));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     * @throws \Exception
     */
    public function add() {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        if ($this->request->is('post')) {
            $User = new User($this->getUser());
            /** @var MessagesOtdTable $MessagesOtdTable */
            $MessagesOtdTable = TableRegistry::getTableLocator()->get('MessagesOtd');
            $requestData = $this->request->getData();
            if (!empty($requestData['MessagesOtd']['date'])) {
                $frozenDate = new \Cake\I18n\Date($requestData['MessagesOtd']['date']);
                $requestData['MessagesOtd']['date'] = $frozenDate->format('Y-m-d');
            }
            $requestData['MessagesOtd']['user_id'] = $User->getId();
            $messageOtd = $MessagesOtdTable->newEntity($requestData);

            $MessagesOtdTable->save($messageOtd);

            if ($messageOtd->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $messageOtd->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            } else {
                if ($this->isJsonRequest()) {
                    $this->serializeCake4Id($messageOtd); // REST API ID serialization
                    return;
                }
            }
            $this->set('messageOtd', $messageOtd);
            $this->viewBuilder()->setOption('serialize', ['messageOtd']);
        }
    }

    /**
     * Edit method
     *
     * @param string|null $id Messages Otd id.
     * @return \Cake\Http\Response|null|void Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function edit($id = null) {
        if (!$this->isApiRequest()) {
            //Only ship HTML template for angular
            return;
        }

        /** @var MessagesOtdTable $MessagesOtdTable */
        $MessagesOtdTable = TableRegistry::getTableLocator()->get('MessagesOtd');

        if (!$MessagesOtdTable->existsById($id)) {
            throw new NotFoundException(__('Invalid message of the day'));
        }

        $messageOtd = $MessagesOtdTable->getMessageOtdByIdForEdit($id);

        if ($this->request->is('get')) {
            $this->set('messageOtd', $messageOtd);
            $this->viewBuilder()->setOption('serialize', ['messageOtd']);
            return;
        }

        if ($this->request->is('post')) {
            $User = new User($this->getUser());
            $requestData = $this->request->getData();
            if (!empty($requestData['MessagesOtd']['date'])) {
                /** @var \Cake\I18n\Date $frozenDate */
                $frozenDate = new \Cake\I18n\Date($requestData['MessagesOtd']['date']);
                $requestData['MessagesOtd']['date'] = $frozenDate->format('Y-m-d');
            }
            $requestData['MessagesOtd']['user_id'] = $User->getId();

            $Entity = $MessagesOtdTable->get($id);
            $Entity = $MessagesOtdTable->patchEntity($Entity, $requestData);

            $MessagesOtdTable->save($Entity);
            if ($Entity->hasErrors()) {
                $this->response = $this->response->withStatus(400);
                $this->set('error', $Entity->getErrors());
                $this->viewBuilder()->setOption('serialize', ['error']);
                return;
            }
            $this->set('messageOtd', $Entity);
            $this->viewBuilder()->setOption('serialize', ['messageOtd']);
        }
    }

    /**
     * @param null $id
     */
    public function delete($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }

        /** @var MessagesOtdTable $MessagesOtdTable */
        $MessagesOtdTable = TableRegistry::getTableLocator()->get('MessagesOtd');

        if (!$MessagesOtdTable->existsById($id)) {
            throw new NotFoundException(__('Invalid message of the day'));
        }

        $messageOtd = $MessagesOtdTable->get($id);

        if ($MessagesOtdTable->delete($messageOtd)) {
            $this->set('success', true);
            $this->set('message', __('Message of the day deleted successfully'));
            $this->viewBuilder()->setOption('serialize', ['success', 'message']);
            return;
        }

        $this->response = $this->response->withStatus(400);
        $this->set('success', false);
        $this->set('message', __('Issue while deleting message of the days'));
        $this->viewBuilder()->setOption('serialize', ['success', 'message']);
    }

    /**
     * @param null $id
     */
    public function notifyUsersViaMail($id = null) {
        if (!$this->request->is('post')) {
            throw new MethodNotAllowedException();
        }
        /** @var MessagesOtdTable $MessagesOtdTable */
        $MessagesOtdTable = TableRegistry::getTableLocator()->get('MessagesOtd');

        if (!$MessagesOtdTable->existsById($id)) {
            throw new NotFoundException(__('Invalid message of the day'));
        }

        $messageOtd = $MessagesOtdTable->get($id, contain: [
            'Usergroups'
        ]);
        if ($messageOtd->get('notify_users')) {
            /** @var UsersTable $UsersTable */
            $UsersTable = TableRegistry::getTableLocator()->get('Users');
            $usergroupIds = [];
            foreach ($messageOtd->get('usergroups') as $usergroup) {
                $usergroupIds[] = $usergroup->get('id');
            }
            $users = $UsersTable->getUsersForMailNotifications($usergroupIds);

            if (!empty($users)) {
                try {

                    /** @var SystemsettingsTable $SystemsettingsTable */
                    $SystemsettingsTable = TableRegistry::getTableLocator()->get('Systemsettings');
                    $_systemsettings = $SystemsettingsTable->findAsArray();

                    $Logo = new Logo();

                    $Mailer = new Mailer();
                    $Mailer->setFrom($_systemsettings['MONITORING']['MONITORING.FROM_ADDRESS'], $_systemsettings['MONITORING']['MONITORING.FROM_NAME']);
                    foreach ($users as $user) {
                        $Mailer->addBcc($user['email']);
                    }

                    $Mailer->setSubject(__('Message of the day ') . $_systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME']);
                    $Mailer->setEmailFormat('both');
                    $Mailer->setAttachments([
                        'logo.png' => [
                            'file'      => $Logo->getSmallLogoPdfPath(),
                            'mimetype'  => 'image/png',
                            'contentId' => '100'
                        ]
                    ]);
                    $frozenDate = new \Cake\I18n\Date($messageOtd->get('date'));
                    $BBCodeParser = new BBCodeParser();
                    $content = $messageOtd->get('content');
                    $content = str_replace("'", "", $content);
                    $htmlContent = $BBCodeParser->asHtml($content);
                    $textContent = strip_tags($htmlContent, '<br>');
                    $textContent = str_replace('<br>', "\n", $textContent);
                    $Mailer->viewBuilder()
                        ->setTemplate('message_otd_mail')
                        ->setVar('systemname', $_systemsettings['FRONTEND']['FRONTEND.SYSTEMNAME'])
                        ->setVar('date', $frozenDate->format('d.m.Y'))
                        ->setVar('expiration_duration', $messageOtd->get('expiration_duration'))
                        ->setVar('title', $messageOtd->get('title'))
                        ->setVar('description', $messageOtd->get('description'))
                        ->setVar('style', $messageOtd->get('style'))
                        ->setVar('htmlContent', $htmlContent)
                        ->setVar('textContent', $textContent);

                    $Mailer->deliver();

                    $this->set('success', true);
                    $this->set('message', __('Message of the day mail sent successfully'));
                    $this->viewBuilder()->setOption('serialize', ['success', 'message']);
                    return;
                } catch (\Exception $ex) {
                    $this->set('success', false);
                    $this->set('message', __('An error occurred while sending test mail: ') . $ex->getMessage());
                    $this->viewBuilder()->setOption('serialize', ['success', 'message']);
                }
            }
        }
    }
}
