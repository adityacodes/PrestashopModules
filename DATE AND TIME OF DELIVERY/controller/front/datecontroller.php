<?php


class DateTimeCustomizerDateControllerModuleFrontController extends ModuleFrontController
{
	/**
	 * @see FrontController::postProcess()
	 */
	public function postProcess()
    {
        // Update carrier selected on preProccess in order to fix a bug of
        // block cart when it's hooked on leftcolumn
        if (parent::step == 3 && Tools::isSubmit('processCarrier')) {
            //$this->processCarrier();
            echo 'Hello Buddy I have been overridden';
        }
    }

}