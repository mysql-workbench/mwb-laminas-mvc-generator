<?php

declare(strict_types=1);

namespace Mwb\LaminasGenerator;

require_once dirname(__FILE__, 2)."/vendor/autoload.php";

use Doctrine\Inflector\InflectorFactory;
use Laminas\Filter\Word\UnderscoreToCamelCase;
use Laminas\Filter\Word\CamelCaseToUnderscore;
use Laminas\Filter\Word\CamelCaseToSeparator;
use Laminas\Filter\Word\CamelCaseToDash;

use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;

use Twig\Environment;
//use Twig\Loader\ArrayLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

/*
use Mezzio\Twig\TwigRenderer;
TODO: install :
DONE  : $ composer require mezzio/mezzio-twigrenderer --ignore-platform-reqs
$ composer require mezzio/mezzio-laminasviewrenderer --ignore-platform-reqs
*/






class ModelExport 
{
	protected $enableOdb = False;
	protected $enableExport = False;
	protected $pathExport = Null;// .'../build'

	protected $mwbDocument;
	protected $whiteTables = [
		//'accounts', 'companies', 'companies_associates',
		// 'statements',
		'operations',
		// 'operation_descriptions',
	]/*Null*/;

	// diagram == controller
	// (ordering diagram by module)
	protected $whiteDiagrams = [
		'Operation',
	];

	static protected function EntityNaming($table_name) {
		$subTablesName = [];
		$subTables = explode('_', $table_name);
		foreach($subTables as $subTable) {
			$ln = strlen($subTable);
			$lastChar = substr($subTable, $ln-1);// ies =>
			if ('s'==$lastChar) {
				$plurialChar = substr($subTable, $ln-3);// ies =>
				if ('ies'==$plurialChar) {
					$subTable = substr($subTable, 0, $ln-3) . 'y';
				} else {
					$subTable = substr($subTable, 0, $ln-1);
				}
			}
			$subTablesName[] = ucfirst($subTable);
		}
		return implode('', $subTablesName);
	}

	static protected function FileNaming($table_name, $suffix='') {
		return self::EntityNaming($table_name).$suffix.'.php';
	}

	static protected function ColumnNaming($column_name) {
		return $column_name;
	}

	public function __construct($filepath) {
		$this->mwbDocument = \Service\MwbDocument::load($filepath);
		$this->pathExport = realpath(__DIR__.'/../data/tmp');
	}
	public function enableExport($enable=True) {
		$this->enableExport = $enable;
		$this->enableOdb = $enable;
	}
	public function enableOutputDataBuffering($enable=True) {
		$this->enableOdb = $enable;
	}

	//public function updateObject($object='table|data|filter|form', $physicalModelIndex=0, $schemataIndex=0): void{}

	public function updateObjectTable($physicalModelIndex=0, $schemataIndex=0): void
	{
		$physicalModel = $this->mwbDocument->grtDocument->documentElement->physicalModels[$physicalModelIndex];
		$schemata      = $physicalModel->catalog->schemata[$schemataIndex];

		$dir = 'module/Application/src/Model';
		`mkdir -p $this->pathExport/$dir`;

		foreach ($schemata->tables as $table) {
			if (empty($this->whiteTables) || in_array($table->name, $this->whiteTables)) {
				$content = '';
				if ($this->enableOdb) {
					ob_start();
				}
				include __DIR__.'/table-laminas/view/gateway/table.tpl.php';
				if ($this->enableOdb) {
					$content = ob_get_clean();
				}
				if ($this->enableExport) {
					$filename = self::FileNaming($table->name, 'Table');// Namespace ?
					file_put_contents($this->pathExport.'/'.$dir.'/'.$filename, $content);
					echo '	+ ' . $this->pathExport.'/'.$dir.'/'.$filename . PHP_EOL;
				}
			}
		}
	}

	public function updateObjectData($physicalModelIndex=0, $schemataIndex=0): void
	{
		$physicalModel = $this->mwbDocument->grtDocument->documentElement->physicalModels[$physicalModelIndex];
		$schemata      = $physicalModel->catalog->schemata[$schemataIndex];

		$dir = 'module/Application/src/Model';
		`mkdir -p $this->pathExport/$dir`;

		foreach ($schemata->tables as $table) {
			if (empty($this->whiteTables) || in_array($table->name, $this->whiteTables)) {
				$content = '';
				if ($this->enableOdb) {
					ob_start();
				}
				include __DIR__.'/table-laminas/view/gateway/data.tpl.php';
				if ($this->enableOdb) {
					$content = ob_get_clean();
				}
				if ($this->enableExport) {
					$filename = self::FileNaming($table->name, 'Data');// Namespace ?
					file_put_contents($this->pathExport.'/'.$dir.'/'.$filename, $content);
					echo '	+ ' . $this->pathExport.'/'.$dir.'/'.$filename . PHP_EOL;
				}
			}
		}
	}

	public function updateObjectFilter($physicalModelIndex=0, $schemataIndex=0): void
	{
		$physicalModel = $this->mwbDocument->grtDocument->documentElement->physicalModels[$physicalModelIndex];
		$schemata      = $physicalModel->catalog->schemata[$schemataIndex];

		$dir = 'module/Application/src/Model';
		`mkdir -p $this->pathExport/$dir`;

		foreach ($schemata->tables as $table) {
			if (empty($this->whiteTables) || in_array($table->name, $this->whiteTables)) {
				$content = '';
				if ($this->enableOdb) {
					ob_start();
				}
				include __DIR__.'/table-laminas/view/gateway/filter.tpl.php';
				if ($this->enableOdb) {
					$content = ob_get_clean();
				}
				if ($this->enableExport) {
					$filename = self::FileNaming($table->name, 'Filter');// Namespace ?
					file_put_contents($this->pathExport.'/'.$dir.'/'.$filename, $content);
					echo '	+ ' . $this->pathExport.'/'.$dir.'/'.$filename . PHP_EOL;
				}
			}
		}
	}

	public function updateObjectForm($physicalModelIndex=0, $schemataIndex=0): void
	{
		$physicalModel = $this->mwbDocument->grtDocument->documentElement->physicalModels[$physicalModelIndex];
		$schemata      = $physicalModel->catalog->schemata[$schemataIndex];

		$dir = 'module/Application/src/Form';
		`mkdir -p $this->pathExport/$dir`;

		foreach ($schemata->tables as $table) {
			if (empty($this->whiteTables) || in_array($table->name, $this->whiteTables)) {
				$content = '';
				if ($this->enableOdb) {
					ob_start();
				}
				include __DIR__.'/table-laminas/view/gateway/form.tpl.php';
				if ($this->enableOdb) {
					$content = ob_get_clean();
				}
				if ($this->enableExport) {
					$filename = self::FileNaming($table->name, 'Form');// Namespace ?
					file_put_contents($this->pathExport.'/'.$dir.'/'.$filename, $content);
					echo '	+ ' . $this->pathExport.'/'.$dir.'/'.$filename . PHP_EOL;
				}
			}
		}
	}

	public function updateObjectTranslatable($physicalModelIndex=0, $schemataIndex=0): void
	{
		$physicalModel = $this->mwbDocument->grtDocument->documentElement->physicalModels[$physicalModelIndex];
		$schemata      = $physicalModel->catalog->schemata[$schemataIndex];

		$dir = 'module/Application/language';
		`mkdir -p $this->pathExport/$dir`;

		foreach ($schemata->tables as $table) {
			if (empty($this->whiteTables) || in_array($table->name, $this->whiteTables)) {
				$content = '';
				if ($this->enableOdb) {
					ob_start();
				}
				include __DIR__.'/table-laminas/view/gateway/translatable.tpl.php';
				if ($this->enableOdb) {
					$content = ob_get_clean();
				}
				if ($this->enableExport) {
					$filename = self::FileNaming($table->name, '.fr_FR');// Namespace ?
					file_put_contents($this->pathExport.'/'.$dir.'/'.$filename, $content);
					echo '	+ ' . $this->pathExport.'/'.$dir.'/'.$filename . PHP_EOL;
				}
			}
		}
	}
	public function updateController($physicalModelIndex=0, $schemataIndex=0): void
	{
		// module/Application/src/Controller/IndexController

		$physicalModel = $this->mwbDocument->grtDocument->documentElement->physicalModels[$physicalModelIndex];
		$schemata      = $physicalModel->catalog->schemata[$schemataIndex];

		$dir = 'module/Application/src/Controller';
		`mkdir -p $this->pathExport/$dir`;

		foreach($physicalModel->diagrams as $diagram) { // workbench.physical.Layer
			if (empty($this->whiteDiagrams) || in_array($diagram->name, $this->whiteDiagrams)) {
				$moduleName = 'Application';
				$moduleCode = lcfirst($moduleName);

				$entityName = $diagram->name;
				$entityCode = lcfirst($diagram->name);

				$roots = Box::sort($diagram->layers);

				$content = '';
				if ($this->enableOdb) {
					ob_start();
				}
				include __DIR__.'/table-laminas/view/gateway/controller.tpl.php';
				if ($this->enableOdb) {
					$content = ob_get_clean();
				}
				if ($this->enableExport) {
					$filename = self::FileNaming($diagram->name, 'Controller');// Namespace ?
					file_put_contents($this->pathExport.'/'.$dir.'/'.$filename, $content);
					echo '	+ ' . $this->pathExport.'/'.$dir.'/'.$filename . PHP_EOL;
				}
			}
		}
	}

	/*
	 * strategy 1 : a layer have one VIEW as select FROM one table
	 */
	protected function findTableFromView($layer, $schemata) {
		$tables = [];
		$views = [];
		foreach($layer->figures as $figure) {
			if (       $figure instanceof \Service\Mwb\Grt\Workbench\Model\ImageFigure) {
				//echo $tab.'^ '.$figure->name . PHP_EOL;
			} else if ($figure instanceof \Service\Mwb\Grt\Workbench\Model\NoteFigure) {
				//echo $tab.'* '.$figure->name . PHP_EOL;
			} else if ($figure instanceof \Service\Mwb\Grt\Workbench\Physical\TableFigure) {
				//echo $tab.'+ '.$figure->name . PHP_EOL;
				$tables[] = $figure->table;//->getOwner();
			} else if ($figure instanceof \Service\Mwb\Grt\Workbench\Physical\ViewFigure) {
				//echo $tab.'x '.$figure->name . PHP_EOL;
				$views[] = $figure;//->getOwner();
			} else {
				//echo $tab.'. '.$figure->name . '(' . get_class($figure) . ')' . PHP_EOL;
			}
		}


		/*
		foreach($tables as $table) {
			echo $table->name . PHP_EOL;
			foreach($table->columns as $column) {
				echo $column->name . PHP_EOL;
			}
		}
		*/


		foreach($views as $view) {
			//\PhpMyAdmin\SqlParser\Context::setMode(\PhpMyAdmin\SqlParser\Context::SQL_MODE_ANSI_QUOTES);
			\PhpMyAdmin\SqlParser\Context::load('ContextMySql50700');
			$query1 = $view->view->sqlDefinition;//'CREATE VIEW `description_edit` AS SELECT s.id FROM statements s JOIN operations o ON o.statements_id = s.id;';

			$lexer = new \PhpMyAdmin\SqlParser\Lexer($query1);
			//var_dump($lexer->list);

			$parser = new \PhpMyAdmin\SqlParser\Parser($lexer->list);
			//print_r($parser->statements[0]);
			//var_dump($parser->statements[0]->options->options[6]);
			//var_dump($parser->statements[0]->name->table);
			$useTable = $parser->statements[0]->select->from[0]->table;
			//var_dump($useTable);
			//$errors = \PhpMyAdmin\SqlParser\Utils\Error::get([$parser]);
			//var_dump($errors);
			
		}


		$table = Null;
		foreach ($schemata->tables as $schemataTable) {
			if ($useTable == $schemataTable->name) {
				$table = $schemataTable;
			}
		}
		return $table;
	}

	protected function findPrimaryKey($table) {
		$primaries = [];
		foreach ($table->indices as $index) {
			if ($index->isPrimary) {
				foreach ($index->columns as $indexColumn) {
					$primaries[] = $indexColumn->referencedColumn->name;
				}
			}
		}
		return $primaries;
	}

	public function updateView($physicalModelIndex=0, $schemataIndex=0): void
	{
		$physicalModel = $this->mwbDocument->grtDocument->documentElement->physicalModels[$physicalModelIndex];
		$schemata      = $physicalModel->catalog->schemata[$schemataIndex];

		foreach($physicalModel->diagrams as $diagram) { // workbench.physical.Layer
			if (empty($this->whiteDiagrams) || in_array($diagram->name, $this->whiteDiagrams)) {

				/** include __DIR__.'/table-laminas/view/gateway/view.tpl.php'; */
				$moduleName = 'Application';
				$moduleCode = lcfirst($moduleName);

				$entityName = $diagram->name;
				$entityCode = lcfirst($diagram->name);

				$roots = Box::sort($diagram->layers);
				foreach($roots as $root) {
					$content = '';
					if ($this->enableOdb) {
						ob_start();
					}

					$layer = $root->container;
					$table = $this->findTableFromView($layer, $schemata);
					$columnsPk = $this->findPrimaryKey($table);

					//$pk = current($columnsPk);

					$url = parse_url($layer->name);
					$actionName = $url["path"];
/*
					$params = [];
					if (isset($url["query"])) {
						$queryParams = [];
						parse_str($url["query"], $queryParams);
						$params = array_keys($queryParams);
					}
					//$url["fragment"];

					//--------------
					$pk = null;
					if (1==count($params))
						$pk = $params[0];//// for delete/edit $id
					//--------------
*/

					switch ($actionName) {
						case 'list':
							include __DIR__.'/table-laminas/view/gateway/view/list.tpl.php';
							break;
						case 'edit':
							include __DIR__.'/table-laminas/view/gateway/view/edit.tpl.php';
							break;
						case 'add':
							include __DIR__.'/table-laminas/view/gateway/view/add.tpl.php';
							break;
						case 'delete':
							include __DIR__.'/table-laminas/view/gateway/view/delete.tpl.php';
							break;
						default:
							//echo '	//Unimplemented CRUD : '.$actionName.PHP_EOL;
							break;
					}
					if ($this->enableOdb) {
						$content = ob_get_clean();
					}
					if ($this->enableExport) {
						$dir = 'module/'.$moduleName.'/view/'.$moduleCode.'/'.lcfirst($diagram->name);
						`mkdir -p $this->pathExport/$dir`;
						$filename = $actionName.'.phtml';//self::FileNaming($diagram->name);// Namespace ?
						file_put_contents($this->pathExport.'/'.$dir.'/'.$filename, $content);
						echo '	+ ' . $this->pathExport.'/'.$dir.'/'.$filename . PHP_EOL;
					}
				}

				/** */
			}
		}
	}

	public function dumpLayer($layer, $level=0): void
	{
		$tab = str_repeat('	', $level);
		foreach($layer->figures as $figure) {
			if (       $figure instanceof \Service\Mwb\Grt\Workbench\Model\ImageFigure) {
				echo $tab.'^ '.$figure->name . PHP_EOL;
			} else if ($figure instanceof \Service\Mwb\Grt\Workbench\Model\NoteFigure) {
				echo $tab.'* '.$figure->name . PHP_EOL;
			} else if ($figure instanceof \Service\Mwb\Grt\Workbench\Physical\TableFigure) {
				echo $tab.'+ '.$figure->name . PHP_EOL;
			} else if ($figure instanceof \Service\Mwb\Grt\Workbench\Physical\ViewFigure) {
				echo $tab.'x '.$figure->name . PHP_EOL;
			} else {
				echo $tab.'. '.$figure->name . '(' . get_class($figure) . ')' . PHP_EOL;
			}
		}
	}
	public function dumpBox($box, $level=0): void
	{
		$tab = str_repeat('	', $level);
		echo $tab.'{'.$box->container->name .'}'. PHP_EOL;
		$this->dumpLayer($box->container, $level+1);
		foreach($box->children as $child) {
			$this->dumpBox($child, $level+1);
		}
	}
	public function dump(): void
	{
		echo ' + List of diagram:' . PHP_EOL;
		$physicalModel = $this->mwbDocument->grtDocument->documentElement->physicalModels[0];
		foreach($physicalModel->diagrams as $diagram) { // workbench.physical.Layer
			echo '	['.$diagram->name .']'. PHP_EOL;
			// $diagram->rootLayer->figures
			// $diagram->rootLayer->groups

			/*
			$roots = Box::sort($diagram->rootLayer->subLayers);
			foreach($roots as $root) {
				$this->dumpBox($root, 2);
			}
			*/
			/*
			// Not good ... rootLayer failled
			foreach($diagram->rootLayer->subLayers as $layer) {
				$this->dumpLayer($layer, 2);
			}
			*/
			/* OK : list unordered all layer
			foreach($diagram->layers as $layer) {
				echo '		{'.$layer->name .'}'. PHP_EOL;
				$this->dumpLayer($layer, 3);
			}
			*/
			$roots = Box::sort($diagram->layers);
			foreach($roots as $root) {
				$this->dumpBox($root, 2);
			}
		}
	}

/*
	public function dumpLayer(): void
	{
		$physicalModel = $this->mwbDocument->grtDocument->documentElement->physicalModels[$physicalModelIndex];
		$schemata      = $physicalModel->catalog->schemata[$schemataIndex];

	}
*/
}


/*
$filepath = realpath(dirname(__FILE__, 2).'/data/aventurine.mwb');
$filepath = realpath(dirname(__FILE__, 2).'/data/aventurine-UserType.mwb');
$exporter = new ModelExport($filepath);
*/
/*
$exporter->enableExport();// dry
$exporter->updateObjectTable();
$exporter->updateObjectData();
$exporter->updateObjectFilter();// <---------- REFACTORING
$exporter->updateObjectTranslatable();
$exporter->updateObjectForm();
$exporter->updateView();// <---------- REFACTORING
$exporter->updateController();// <---------- REFACTORING
*/
//$exporter->updateTableFactory();
//$exporter->updateTableHydrator();

// TODO create DeviseHydrator.php
// TODO create OperationTableFactory.php
// TODO create Operation.php
// TODO create routes module.config.php

// FIXE : How to use Application\Filter\ToDevise; in OperationForm.php
// FIXME : Dans OperationTable::getOperation()  utiliser le column->name et pas le table_name (statements_id vs statement_id)

// OperationTable "$rowset = $this->tableGateway->select(['id' => $id, 'statements_id' => $statements_id]);" doit etre remplacé par 'statement_id' (sans 's')

// Dans OperationForm (name' => 'parent_id',) n'a pas de label

/* + ajouter les options
		'value_options' => [
		    '0' => 'ALL',
		],
  - supprimer le dur :
 add('aaccounts_id')...
*/

/*
see in : OperationForm.php
		        'validators' => [
		            [
		                'name'    => InArray::class,
		                'options' => [
		                    'haystack' => ['1'],// '1' => 'ALL'
		                ],
		            ],
*/

// !!!!!!!!!!!!! Mettre a jour la base de donné ...
// # mysql -u sciaveo -p aventurine < "$(pwd)/../data/tmp/000-aventurine.sql"
// pass
//	docker exec -i ovh_mysql mysql -h 127.0.0.1 -P 3306 -D aventurine -u sciaveo -p'pass' < 000-aventurine.sql


var_dump('Starting generation (TODO: Controller::view');
//$exporter->dump(); ->dumpLayers()



/*

$configTemplates = [
    'templates' => [
        'extension' => 'file extension used by templates; defaults to html.twig',
        'paths' => [
            // namespace / path pairs
            //
            // Numeric namespaces imply the default/main namespace. Paths may be
            // strings or arrays of string paths to associate with the namespace.
        ],
    ],
    'twig' => [
        'autoescape' => 'html', // Auto-escaping strategy [html|js|css|url|false]
        'cache_dir' => 'path to cached templates',
        'assets_url' => 'base URL for assets',
        'assets_version' => 'base version for assets',
        'extensions' => [
            // extension service names or instances
        ],
        'globals' => [
            // Global variables passed to twig templates
            'ga_tracking' => 'UA-XXXXX-X'
        ],
        'optimizations' => -1, // -1: Enable all (default), 0: disable optimizations
        'runtime_loaders' => [
            // runtime loader names or instances
        ],
        'timezone' => 'default timezone identifier, e.g. America/New_York',
        'auto_reload' => true, // Recompile the template whenever the source code changes
    ],
];


// Create the engine instance:
//$loader = new ArrayLoader($configTemplates);
$loader = new FilesystemLoader(__DIR__.'/template-laminas/controller');
$twig = new Environment($loader, [
	'cachs' => False
]);
// Configure it:
// $twig->addExtension(new CustomExtension());
// $twig->loadExtension(new CustomExtension();
// Inject:
//$renderer = new TwigRenderer($twig);
$output = $twig->render('index/index.php.twig', [
	'module' => 'Application',
	'controller' => 'Index',
	'action' => 'index',
]);
echo $output . PHP_EOL;
*/


//$exporter->updateObjectTable();
//$exporter->updateObjectData();
//$exporter->updateObjectFilter();
//$exporter->updateObjectForm();
//$exporter->updateObjectTranslatable();

//CRUD(); Controller/view
//$exporter->updateView();// TODO view
//$exporter->updateController();// TODO view

//??$exporter->updateConfig();// TODO routes, Table|ControllerFactory, ...



//?$exporter->updateTestCRUD();
//?$exporter->updateTestTable();
//?$exporter->updateTestFilter();
//?$exporter->updateTestValidator();
//?$exporter->updateTestForm();


// Create mapping SQL=>PHP for Table, Column, Type...
// resolve/mappin => table companies => entity Company


//? queryImport($grtDocument);
//? entityImport($grtDocument);

