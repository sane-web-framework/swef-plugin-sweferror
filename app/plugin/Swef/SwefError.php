<?php

namespace Swef;

class SwefError extends \Swef\Bespoke\Plugin {

/*
    PROPERTIES
*/

    public  $contentType;
    public  $email;
    public  $ep;
    public  $endpoint;
    public  $endpointE;
    public  $endpointError;
    public  $error;
    public  $httpE;
    public  $httpError;
    public  $httpTemplate;
    public  $message;
    public  $template;
    public  $templateDefault;

/*
    EVENT HANDLER SECTION
*/

    public function __construct ($page) {
        // Always construct the base class - PHP does not do this implicitly
        parent::__construct ($page,'\Swef\SwefError');
    }

    public function __destruct ( ) {
        // Always destruct the base class - PHP does not do this implicitly
        parent::__destruct ( );
    }

/*
IS THIS NEEDED?
    public function _anyRoute ($c) {
        foreach ($this->page->swef->usergroups as $ug) {
            foreach ($this->page->swef->routers as $r) {
                if (preg_match($r[SWEF_COL_USERGROUP_PREG],$ug[SWEF_COL_USERGROUP])) {
                    if (preg_match($r[SWEF_COL_ENDPOINT_PREG],$c)) {
                        return $r;
                    }
                }
            }
        }
        return SWEF_BOOL_FALSE;
    }
*/

    public function _error ( ) {
        $c                              = $this->getLiveEndpoint ();
        $e                              = null;
        $this->error                    = SWEF_STR__EMPTY;
        $this->template                 = $this->templateDefault;
        $this->page->diagnosticAdd ('Analysing framework identification for an error');
        $this->page->diagnosticAdd ('    endpoint: '.$c->endpoint);
        // Check endpoint has a valid format
        if (!preg_match(SWEF_ENDPOINT_URI_PREG_MATCH,$c->endpoint)) {
            $e                          = SWEF_HTTP_STATUS_CODE_404;
            $this->error                = $this->config[sweferror_404_message];
            $this->page->diagnosticAdd ('    Endpoint name is not valid');
        }
        elseif (!is_readable(SWEF_DIR_ENDPOINT.'/'.$c->endpoint.SWEF_STR_EXT_PHP)) {
            if (!$c->template) {
                $e                      = SWEF_HTTP_STATUS_CODE_404;
                $this->error            = $this->config[sweferror_404_message];
                $this->page->diagnosticAdd ('    Endpoint has neither script nor a template');
            }
            elseif ($c->template[SWEF_COL_NEEDS_SCRIPT]) {
                $e                      = SWEF_HTTP_STATUS_CODE_404;
                $this->error            = $this->config[sweferror_404_message];
                $this->page->diagnosticAdd ('    Endpoint has no script but template needs one:');
                $this->template         = $c->template;
            }
        }
        elseif (!$c->router) {
            $this->page->diagnosticAdd ('    Endpoint HAS NO ROUTER => UNAUTHORISED');
            $e                          = SWEF_HTTP_STATUS_CODE_403;
            $this->error                = $this->config[sweferror_403_message];
            if ($c->template) {
                $this->template         = $c->template;
            }
        }
        $this->page->diagnosticAdd ('e: '.$e);
        $this->ep                       = $c->endpoint;
        return $e;
    }


    public function _on_pluginsSetAfter ( ) {
       $this->templateDefault   = array (
            SWEF_COL_TEMPLATE       => $this->config[sweferror_403_template]
           ,SWEF_COL_CONTENTTYPE    => $this->config[sweferror_403_content_type]
        );
    }

    public function _on_pageIdentifyAfter ( ) {
        $this->httpE                    = $this->_error ();
        $this->httpError                = $this->error;
        if (is_readable(SWEF_DIR_TEMPLATE.'/'.$this->template[SWEF_COL_TEMPLATE])) {
            $this->httpTemplate = $this->template;
        }
        else {
            $this->httpTemplate = $this->templateDefault;
        }
        $this->page->diagnosticAdd ('HTTP error: '.$this->httpError);
        $this->page->diagnosticAdd ('HTTP template: '.$this->httpTemplate[SWEF_COL_TEMPLATE]);
    }

    public function _on_headersBefore ( ) {
        if ($this->httpE) {
            $this->page->swef->statusHeader ($this->httpE);
        }
        else {
            $this->page->swef->statusHeader (SWEF_HTTP_STATUS_CODE_200);
        }
        return SWEF_BOOL_TRUE;
    }

    public function _on_pageScriptBefore ( ) {
        if (!$this->httpE) {
            return SWEF_BOOL_TRUE;
        }
        $this->page->diagnosticAdd ('Pushed page error: '.$this->httpError);
        if (strlen(trim($this->httpError))) {
            $this->notify ($this->httpError);
        }
/*
        $this->page->template   = $this->httpTemplate;
print_r ($this->page->template);
die ();
        $this->page->diagnosticAdd ('Page template set to '.$this->page->template[SWEF_COL_TEMPLATE]);
*/
        return SWEF_BOOL_FALSE;
    }

    public function _on_endpointIdentifyAfter ( ) {
        $this->endpointE                 = $this->_error ();
        $this->endpointError             = $this->error;
        $this->endpoint                  = $this->ep;
        $this->page->diagnosticAdd ('Endpoint "'.$this->endpoint.'" error: '.$this->endpointError);
    }

    public function _on_endpointScriptBefore ( ) {
        if (!$this->endpointE) {
            return SWEF_BOOL_TRUE;
        }
        if ($this->httpE) {
            // If page also has error just cancel pulled script
            return SWEF_BOOL_FALSE;
        }
        $this->page->diagnosticAdd ('Pulled component error');
        return SWEF_BOOL_FALSE;
    }

    public function _on_endpointTemplateBefore ( ) {
        if (!$this->endpointE) {
            return SWEF_BOOL_TRUE;
        }
        if ($this->httpE) {
            // If page also has error just cancel pulled template
            return SWEF_BOOL_FALSE;
        }
        $this->page->diagnosticAdd ('Require()ing template: '.sweferror_file_endpoint);
        require sweferror_file_endpoint;
        return SWEF_BOOL_FALSE;
    }


/*
    DASHBOARD SECTION
*/


    public function _dashboard ( ) {
        require_once sweferror_file_dash;
    }

    public function _info ( ) {
        $info   = __FILE__.SWEF_STR__CRLF;
        $info  .= SWEF_COL_CONTEXT.SWEF_STR__EQUALS;
        $info  .= $this->page->swef->context[SWEF_COL_CONTEXT];
        return $info;
    }

}

?>
