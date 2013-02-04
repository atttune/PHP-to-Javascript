<?php

namespace PHPToJavascript;






abstract class CodeScope{

	use SafeAccess;

	var $bracketCount = 0;

	var $name;

	var $defaultValues = array();

	/** @var CodeScope */
	var $parentScope;

	var $childScopes = array();

	function addChild($scope){
		//$this->childScopes[] = $scope;
		$this->jsElements[] = $scope;
	}

	var $jsElements = array();

	function	addJS($jsString){
		$this->jsElements[] = $jsString;
	}


	function	getJS(){
		return $this->getJSRaw();
	}

	function	markMethodsStart(){
		throw new Exception("This should only be called on ClassScope");
	}

	function	getJSRaw(){
		//$js = "\n//Beginning of scope ".get_class($this)." ".$this->getName()."\n";

		$js = "";

		foreach($this->jsElements as $jsElement){
			if($jsElement instanceof CodeScope){
				$js .= $jsElement->getJS();
			}
			else if(is_string($jsElement)){
				$js .= $jsElement;
			}
			else{
				throw new Exception("Unknown type in this->jsElements of type [".get_class($jsElement)."]");
			}
		}

		//$js .= "\n//End of scope ".get_class($this)." ".$this->getName()."\n";

		$js .= "\n";
		$js .= "\n";

		foreach($this->jsElements as $jsElement){
			if($jsElement instanceof CodeScope){
				$js .= $jsElement->getDelayedJS($this->getName());
				$js .= "\n";
			}
		}


		return $js;
	}

	function	getDelayedJS($parentScopeName){
		return "";
	}


	/**
	 * @var string[]
	 */
	public $scopedVariables = array();

	/**
	 * @param $variableName
	 * @param $isClassVariable - whether the variable was prefixed by $this
	 * @return mixed
	 *
	 * For a given variable name, try to find the variable in the current scope.
	 *
	 * //TODO - change $isClassVaraible to be a flag to support FLAG_THIS, FLAG_SELF, FLAG_STATIC, FLAG_PARENT
	 */
	abstract	function	getScopedVariableForScope($variableName, $isClassVariable);
	abstract	function getType();

	function	getScopedVariable($variableName, $isClassVariable){
		$result = $this->getScopedVariableForScope($variableName, $isClassVariable);

		if($result == NULL){
			if($this->parentScope != NULL){
				return $this->parentScope->getScopedVariable($variableName, $isClassVariable);
			}
		}

		return $result;
	}

	function getName(){
		return $this->name;
	}

	function __construct($name, $parentScope){
		$this->name = $name;
		$this->parentScope = $parentScope;
	}

	function	pushBracket(){
		$this->bracketCount += 1;
	}

	function	popBracket(){
		$this->bracketCount -= 1;
		if($this->bracketCount <= 0){
			return TRUE;
		}

		return FALSE;
	}

	function	addScopedVariable($variableName, $variableFlag){
		$cVar = cvar($variableName);

		if(PHPToJavascript_TRACE == TRUE){
			echo "Added variable $variableName to scope ".get_class($this)."\n";
		}

		if(array_key_exists($cVar, $this->scopedVariables) == FALSE){
			$this->scopedVariables[$cVar] = $variableFlag;// $this->name.".".$variableName;
		}
	}

	function	setDefaultValueForPreviousVariable($value){

		$allKeys = array_keys($this->scopedVariables);
		if(count($allKeys) == 0){
			throw new Exception("Trying to add default variable but not variables found yet.");
		}

		$variableName = $allKeys[count($allKeys) - 1];

		$this->defaultValues[$variableName] = convertPHPValueToJSValue($value);
	}

	function	getVariablesWithDefaultParameters(){
		return $this->defaultValues;
	}

	function	startOfFunction(){
		return FALSE;
	}

}




?>