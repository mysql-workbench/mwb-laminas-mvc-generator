# Prerequist
 - Twig
 - SQLParser (phpMyAdmin)

# 1st try of writing an MVC CRUD

Controller generation

```php
<?php

declare(strict_types=1);

namespace Application\Controller;

use Application\Model\Operation;
use Application\Model\OperationTable;
use Application\Form\OperationForm;

use Laminas\Db\TableGateway\TableGateway;
use Laminas\Db\Adapter\Adapter;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;


class OperationController extends AbstractActionController
{
	// Add this property:
	private $table;

	// Add this constructor:
	public function __construct(OperationTable $table)
	{
		$this->table = $table;
	}


	/* CREATE[POST|GET] */
	public function addAction()
	{
		$request = $this->getRequest();
statements_id
parent_id
		$mandatory_statements_id = (int) $this->params()->fromRoute('statements_id', 0);
		$mandatory_parent_id = (int) $this->params()->fromRoute('parent_id', 0);

		$form = new OperationForm(False, 'create_operation');
		$form->get('submit')->setValue('Add');
		$form->get('statements_id')->setValue($route_statements_id);// error if POST exist

		if (! $request->isPost()) {
			return ['form' => $form];
		}

		$operation = new Operation();
		//$form->setHydrator(new ClassMethodsHydrator());
		$form->bind($operation);
		$inputFilter = $operation->getInputFilter(False, True);// hasPrimaryKey=False
		$form->setInputFilter($inputFilter);
		$form->setData($request->getPost());

		if (! $form->isValid()) {
			return ['form' => $form];
		}

		$this->table->saveOperation($operation);
		return $this->redirect()->toRoute('application');
	}

// [READ]
// [READ]
// [UPDATE]
// [DELETE]
// [DELETE]
}

```
