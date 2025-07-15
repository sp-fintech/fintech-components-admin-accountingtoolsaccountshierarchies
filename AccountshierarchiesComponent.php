<?php

namespace Apps\Fintech\Components\Accounting\Tools\Accountshierarchies;

use Apps\Fintech\Packages\Accounting\Accounts\AccountingAccounts;
use Apps\Fintech\Packages\Accounting\Banks\AccountingBanks;
use Apps\Fintech\Packages\Accounting\Tools\Accountshierarchies\AccountingToolsAccountshierarchies;
use Apps\Fintech\Packages\Adminltetags\Traits\DynamicTable;
use System\Base\BaseComponent;

class AccountshierarchiesComponent extends BaseComponent
{
    use DynamicTable;

    protected $hierarchiesPackage;

    protected $accountsPackage;

    protected $banksPackage;

    public function initialize()
    {
        $this->hierarchiesPackage = $this->usePackage(AccountingToolsAccountshierarchies::class);

        $this->accountsPackage = $this->usePackage(AccountingAccounts::class);

        $this->banksPackage = $this->usePackage(AccountingBanks::class);
    }

    /**
     * @acl(name=view)
     */
    public function viewAction()
    {
        if (isset($this->getData()['id'])) {
            $this->view->banks = $this->banksPackage->getAll()->accountingbanks;

            $this->view->accountsStructure = [];

            $this->view->acTypes = $this->accountsPackage->getAvailableAccountTypes();

            if ($this->getData()['id'] != 0) {
                $hierarchy = $this->hierarchiesPackage->getById((int) $this->getData()['id']);

                if (!$hierarchy) {
                    return $this->throwIdNotFound();
                }

                if ($hierarchy['hierarchy']) {
                    if (is_string($hierarchy['hierarchy'])) {
                        $hierarchy['hierarchy'] = $this->helper->decode($hierarchy['hierarchy'], true);
                    }

                    if (count($hierarchy['hierarchy']) > 0) {
                        $this->view->accountsStructure = $this->seqAccounts($hierarchy['hierarchy']);
                    } else {
                        $this->view->accountsStructure = $hierarchy['hierarchy'];
                    }
                }
                // trace([$this->view->accountsStructure]);
                $this->view->hierarchy = $hierarchy;
            }

            $this->view->pick('accountshierarchies/view');

            return;
        }

        $controlActions =
            [
                // 'disableActionsForIds'  => [1],
                'actionsToEnable'       =>
                [
                    'edit'      => 'accounting/tools/accountshierarchies',
                    'remove'    => 'accounting/tools/accountshierarchies/remove'
                ]
            ];

        $this->generateDTContent(
            package: $this->hierarchiesPackage,
            postUrl: 'accounting/tools/accountshierarchies/view',
            postUrlParams: null,
            columnsForTable: ['name'],
            withFilter : true,
            columnsForFilter : ['name'],
            controlActions : $controlActions,
            dtNotificationTextFromColumn: 'name'
       );

        $this->view->pick('accountshierarchies/list');
    }

    /**
     * @acl(name=add)
     */
    public function addAction()
    {
        $this->requestIsPost();

        $this->hierarchiesPackage->addAccountingAccountshierarchies($this->postData());

        $this->addResponse(
            $this->hierarchiesPackage->packagesData->responseMessage,
            $this->hierarchiesPackage->packagesData->responseCode,
            $this->hierarchiesPackage->packagesData->responseData ?? []
        );
    }

    /**
     * @acl(name=update)
     */
    public function updateAction()
    {
        $this->requestIsPost();

        $this->hierarchiesPackage->updateAccountingAccountshierarchies($this->postData());

        $this->addResponse(
            $this->hierarchiesPackage->packagesData->responseMessage,
            $this->hierarchiesPackage->packagesData->responseCode,
            $this->hierarchiesPackage->packagesData->responseData ?? []
        );
    }

    /**
     * @acl(name=remove)
     */
    public function removeAction()
    {
        $this->requestIsPost();

        $this->hierarchiesPackage->removeAccountingAccountshierarchies($this->postData()['id']);

        $this->addResponse(
            $this->hierarchiesPackage->packagesData->responseMessage,
            $this->hierarchiesPackage->packagesData->responseCode,
            $this->hierarchiesPackage->packagesData->responseData ?? []
        );
    }

    private function seqAccounts($accounts)
    {
        $accounts = msort($accounts, 'seq', SORT_REGULAR, SORT_ASC, true);

        foreach ($accounts as $key => &$value) {
            if (isset($value['childs'])) {
                $value['childs'] = $this->seqAccounts($value['childs']);
            }
        }

        return $accounts;
    }

    public function getNewAccountUUIDAction()
    {
        $this->requestIsPost();

        $this->addResponse('UUID Generated', 0, ['uuid' => str_replace('-', '', $this->random->uuid())]);
    }
}