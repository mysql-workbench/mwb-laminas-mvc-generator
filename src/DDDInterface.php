<?php

declare(strict_types=1);

namespace Inspection;


require_once dirname(__FILE__, 2)."/vendor/autoload.php";

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\NodeTraverser;
use PhpParser\Node;
use PhpParser\NodeFinder;

/**
// Ils ont été fabriqués dans la ville de Tipoca City sur la planète Kamino
https://github.com/tipoca/automatos




Les services:

 - Diagnostic ( $helper() difficile a comprendre)
 - Inspection (detect la strategy)
     - RouteInspection
     - ConfigInspection
     - ReturnActionInspection
 - Introspection (crée ses propre strategy)
 - Generator
     - Strategy
         - QuickStartStrategy
         - StandardStrategy
             - StandardNaming
             - StandardNavigation
         - PatternStrategy                     BestPracticeStrategy
         - GenericStrategy
         - AdvencedStrategy
         - ExpertStrategy
             - ExpertNaming
             - FlashMessageNavigation
             - AjaxForm (extjs, ...)
             - APICore
         - NativeStrategy
         - MvcStrategy
         - OctogonalStrategy
*/



interface InspectorInterface // reflechir sur le code existant en se posant certaines questions
{
	function inspect($options=Null);
}
abstract class HierarchicInspector implements InspectorInterface // reflechir sur le code existant en se posant certaines questions
{
	public string $name;

	private ?HierarchicInspector $owner=Null;
	function __construct($owner=Null) {
		$this->owner = $owner;
	}
	public function getOwner() {
		return $this->owner;
	}
	public function setOwner($owner) {
		$this->owner = $owner;
	}
}

class Inspector extends HierarchicInspector // reflechir sur le code existant en se posant certaines questions
{
	public HierarchicInspector  $architecture;
	function inspect($options=Null) {
		$this->name = 'LaminasProjectSkeleton';
		$architecture = new MvcInspector($this);
		$architecture->inspect();
		$this->architecture = $architecture;
	}
}
class MvcInspector extends HierarchicInspector
{
	/** @var array<HierarchicInspector> $modules */
	public array $modules = [];
	//function __construct($owner) {parent::__construct($owner);}
	function inspect($options=Null) {
		$this->name = 'Mvc';
		$module = new ModuleMvcInspector($this);
		$module->inspect();
		//$this->modules[] = $module;
	}
}
class ModuleMvcInspector extends HierarchicInspector
{
	/** @var array<HierarchicInspector> $controllers */
	public array $controllers = [];
	//function __construct($owner) {parent::__construct($owner);}
	function inspect($options=Null) {
		$mvcInspector = $this->getOwner();
		$this->name = 'Application';
		$mvcInspector->modules[$this->name] = $this;
		$controller = new ControllerMvcInspector($this);
		$controller->inspect(__DIR__.'/../module/Application/src/Controller/OperationController.php');
		//$this->controllers[] = $controller;

	}
}
class ControllerMvcInspector extends HierarchicInspector
{
	public $filepath;
	/** @var array<HierarchicInspector> $actions */
	public array $actions = [];
	//function __construct($owner) {parent::__construct($owner);}
	function inspect($options=Null) {
		$this->filepath = realpath($options);

		if (empty($this->filepath)) {
			throw new \Exception('$filepath is empty');
		}

		$code = file_get_contents($this->filepath);
		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		try {
		    $ast = $parser->parse($code);
		} catch (Error $error) {
		    echo "Parse error: {$error->getMessage()}\n";
		    return;
		}

		/*
		$dumper = new NodeDumper;
		echo $dumper->dump($ast) . "\n";
		*/

		/*
		$nodeFinder = new NodeFinder();
		$methods = $nodeFinder->findInstanceOf($stmts, Node\Stmt\ClassMethod::class);
		*/

		$trace = False;

		// search namespace
		foreach ($ast as $nodeNamespace) {
			//PhpParser\Node\Stmt\Declare_
			if ($nodeNamespace instanceof \PhpParser\Node\Stmt\Namespace_) {
				if ($trace) echo  'namespace = ' . '\\' . implode('\\', $nodeNamespace->name->parts) . PHP_EOL;

				foreach ($nodeNamespace->stmts as $nodeClass) {
					if ($nodeClass instanceof \PhpParser\Node\Stmt\Class_) {
						$className = $nodeClass->name->name;
						$lenght = strlen($className);
						if ($lenght > strlen('Controller')) {
							$suffix = substr($className, -strlen('Controller'));
							if ('Controller' === $suffix) {
								$controllerName = substr($className, 0, -strlen('Controller'));
		$this->name = $controllerName;
		$moduleInspector = $this->getOwner();
		$moduleInspector->controllers[$controllerName] = $this;

								if ($trace) echo  'controller = ' . $controllerName . PHP_EOL;
								if ($trace) echo  '  extends = ' . implode('\\', $nodeClass->extends->parts) . PHP_EOL;
								//echo  '  implements = ' . implode('\\', $nodeStmt->implements) . PHP_EOL;

								foreach ($nodeClass->stmts as $nodeMethod) {
									if ($nodeMethod instanceof \PhpParser\Node\Stmt\Property) {
									}
									if ($nodeMethod instanceof \PhpParser\Node\Stmt\ClassMethod) {
										$methodName = $nodeMethod->name->name;
										$lenght = strlen($methodName);
										if ($lenght > strlen('Action')) {
											//$actionName = substr($methodName, 0, -strlen('Action'));
											$suffix = substr($methodName, -strlen('Action'));
											if ('Action' === $suffix) {
												$actionName = substr($methodName, 0, -strlen('Action'));
												if ($trace) echo  '			action = ' . $actionName . PHP_EOL;
		$action = new ActionControllerMvcInspector();
		$action->inspect(['actionName'=>$actionName, 'nodeMethod'=> $nodeMethod]);
		$this->actions[$actionName] = $action;

											} else {
												if ($trace) echo  '		function = ' . $methodName . PHP_EOL;
											}
										} else {
											if ($trace) echo  '		function = ' . $methodName . PHP_EOL;
										}
		//echo get_class($nodeMethod).PHP_EOL;
									}
								}
							} else {
								if ($trace) echo  'class = ' . $className . PHP_EOL;
							}
						} else {
								if ($trace) echo  'class = ' . $className . PHP_EOL;
						}
					}
				}
			}
		}
	}
}
class ActionControllerMvcInspector extends HierarchicInspector
{

	/** @var array<InspectorInterface> $actions */
	//public array $actions = [];
	//function __construct($owner) {parent::__construct($owner);}
	/**
	 * ['actionName'=>'index', 'nodeMethod'=> Node] $options
	 */
	function inspect($options=Null) {
		$this->name = $options['actionName'];
		$node = $options['nodeMethod'];
		// Deux strategie de retour d'une action, 

		if ('add' !== $this->name) {
			return;
		}

		$nodeFinder = new \PhpParser\NodeFinder();
		$returns = $nodeFinder->findInstanceOf($node->stmts, \PhpParser\Node\Stmt\Return_::class);

/*
1er return of listAction
                            2: Stmt_Return(
                                expr: Expr_New(
                                    class: Name(
                                        parts: array(
                                            0: ViewModel
                                        )
                                    )
                                    args: array(
                                        0: Arg(
                                            name: null
                                            value: Expr_Array(
                                                items: array(
                                                    0: Expr_ArrayItem(
                                                        key: Scalar_String(
                                                            value: operations
                                                        )
                                                        value: Expr_MethodCall(
                                                            var: Expr_PropertyFetch(
                                                                var: Expr_Variable(
                                                                    name: this
                                                                )
                                                                name: Identifier(
                                                                    name: table
                                                                )
                                                            )
                                                            name: Identifier(
                                                                name: fetchAll
                                                            )
                                                            args: array(
                                                            )
                                                        )
                                                        byRef: false
                                                        unpack: false
                                                    )
                                                )
                                            )
                                            byRef: false
                                            unpack: false
                                        )
                                    )
                                )
                            )


*/
/*
1er return of addAction
                                    0: Stmt_Return(
                                        expr: Expr_Array(
                                            items: array(
                                                0: Expr_ArrayItem(
                                                    key: Scalar_String(
                                                        value: form
                                                    )
                                                    value: Expr_Variable(
                                                        name: form
                                                    )
                                                    byRef: false
                                                    unpack: false
                                                )
                                            )
                                        )
                                    )

*/
/*
3eme return of addAction
                            11: Stmt_Return(
                                expr: Expr_MethodCall(
                                    var: Expr_MethodCall(
                                        var: Expr_Variable(
                                            name: this
                                        )
                                        name: Identifier(
                                            name: redirect
                                        )
                                        args: array(
                                        )
                                    )
                                    name: Identifier(
                                        name: toRoute
                                    )
                                    args: array(
                                        0: Arg(
                                            name: null
                                            value: Scalar_String(
                                                value: operation
                                            )
                                            byRef: false
                                            unpack: false
                                        )
                                    )
                                )
                            )


*/

/*
        return [...];

        ---

        return new ViewModel([
            'content' => 'Placeholder page'
        ]);

        ---

        $helper = $this->plugin('createHttpNotFoundModel');
        return $helper($event->getResponse());

*/

		foreach($returns as $returnStmts) {
			//echo gettype($return).PHP_EOL;
			//echo get_class($return->expr).PHP_EOL;
			if (       $returnStmts->expr instanceof \PhpParser\Node\Expr\Array_) {
				echo 'return [];' . PHP_EOL;
			} else if ($returnStmts->expr instanceof \PhpParser\Node\Expr\MethodCall) {
				// return $this->redirect()->toRoute('operation');
				echo 'return ';
				echo '$'.$returnStmts->expr->var->var->name;
				echo '->'.$returnStmts->expr->var->name->name.'()';
				echo '->'.$returnStmts->expr->name->name.'(...);' . PHP_EOL;
				// return $this->redirect()->toRoute(...);
			} else {
			}
			
		}

		/*
		foreach ($node as $nodeStmt) {
			echo get_class($nodeMethod).PHP_EOL;
		}
		*/

	}
	function inspectReturn($options=Null) {
		$options = $node;

		// return ['form' => $form];
		// return $this->redirect()->toRoute('operation');
	}
}

$inspector = new Inspector();
$inspector->inspect();
echo $inspector->architecture->modules['Application']->controllers['Operation']->actions['add']->name . PHP_EOL;








/*
namespace Introspection;

class Introspector // reflechir sur lui meme en se posant certaines questions
{
	function introspect() {}
	function examine() {}
}
class MvcIntrospector extends Introspector
{
	function inspect() {}
}

$controllerFilepath = __DIR__.'/../module/Application/src/Controller/OperationController.php';
$introspector = new MvcIntrospector();
$controllerFilepath
*/


/**

Laminas-vision
module :
 - Analyser (Analyse la structure et les pattern de code)
 - Synthesizer (Déduit la strategie de generation a partir de l'analyse, capable de créer et de moduler des son)
 - Strategist (genere la strategie  de generation, naming dossier)
 - Architect (Elaborer la structure ideale selon les pattern detecté)
 - Observer (Scrute le code existant)


[Code existant] -> [Analyser] -> [Strategist] -> [Generator]

*/



class StrategyExport 
{
}

class FlashControllerStrategy extends StrategyExport
{
}

class AjaxStrategy extends StrategyExport
{
}

/* Filter */

/*class BlackListFilterExport
{
}

class WhiteListFilterExport
{
}

class FilterExport
{
}
$filter = new FilterExport();
*/

/*
laminas-scaffold
bin/
	make:project
	make:module
		ModuleGenerator
	make:controller
		ControllerGenerator
	make:acction
	make:view
		ViewGenerator
	make:table
		TableGenerator
	make:model
		EntityGenerator
	make:filter
	make:hydrator
		FormGenerator
	make:test

class LaminasTool


class MwbDocument


// Scan les controller existant et en déduit une stratégie de construction
automatos-php
automatos-laminas
automatos-symphony
automatos-laravel
*/

interface DDDInterface
{
	function push() {
	}
	function pull() {
	}
	function flush() {
	}
}

abstract class MwbExport implements DDDInterface
{
}

class DumpGenerator extends MwbExport {}




abstract class LaminasComposer extends MwbExport
{
}

abstract class ScaffolderGenerator extends MwbExport
{
}

// Laminas-interoperabolity
// Composite Lamina Model Design with the Use of Heuristic Optimization


// Laminas-heuristics (Qui sert à la découverte. L’heuristique est la « discipline qui se propose de dégager les règles de la recherche scientifique[)
// Laminas-learning
// Laminas-genome
// Laminas-morphology
// Laminas-signature


// Laminas-introspection / introspector
// Laminas-reflection / reflector
// Laminas-exctraction
// Laminas-resolver
// Laminas-scanner
// Laminas-observer
// Laminas-analyser

// LaminasConstructa
// LaminasFlow
// LaminasForge
// LaminasArtisant
// LaminasIndustria ( l'habileté à faire quelque chose )
class LaminasBuilder extends ScaffolderGenerator
{
/*
	EntityHydrator
	TableHydrator
	ControllerHydrator
	ViewHydrator
	FormHydrator
	ModuleHydrator
*/
}

