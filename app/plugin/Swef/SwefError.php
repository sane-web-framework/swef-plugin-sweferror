<?php

namespace Swef;

class SwefError extends \Swef\Bespoke\Plugin {

/*
    PROPERTIES
*/

    public  $contentType;
    public  $email;
    public  $error;
    public  $httpE;
    public  $httpHeader                 = SWEF_HTTP_STATUS_MSG_200;
    public  $template;

/*
    EVENT HANDLER SECTION
*/

    public function __construct ($page) {
        // Get definitions
        require_once SWEF_CONFIG_PATH.'/Swef/SwefError.define.php';
        // Always construct the base class - PHP does not do this implicitly
        parent::__construct ($page,'\Swef\SwefError');
    }

    public function __destruct ( ) {
        // Always destruct the base class - PHP does not do this implicitly
        parent::__destruct ( );
    }

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

    public function _error ( ) {
        $e                              = null;
        $this->page->diagnosticAdd ('Analysing framework identification for an error');
        $c                              = $this->getLiveEndpoint ();
        $this->page->diagnosticAdd ('    endpoint: '.$c->endpoint);
        // Check endpoint has a valid format
        if (!preg_match(SWEF_ENDPOINT_URI_PREG_MATCH,$c->endpoint)) {
            $e                          = SWEF_HTTP_STATUS_CODE_404;
            $this->page->diagnosticAdd ('    Endpoint name is not valid');
        }
        elseif (!is_readable(SWEF_DIR_ENDPOINT.'/'.$c->endpoint.SWEF_STR_EXT_PHP)) {
            if (!$c->template) {
                $e                      = SWEF_HTTP_STATUS_CODE_404;
                $this->page->diagnosticAdd ('    Endpoint has neither script nor a template');
            }
            elseif ($c->template[SWEF_COL_NEEDS_SCRIPT]) {
                $this->page->diagnosticAdd ('    Endpoint has no script but template needs one');
                $e                      = SWEF_HTTP_STATUS_CODE_404;
            }
        }
        elseif (!$c->router) {
            $this->page->diagnosticAdd ('    Endpoint HAS NO ROUTER => UNAUTHORISED');
            $e                          = SWEF_HTTP_STATUS_CODE_403;
        }
        $this->page->diagnosticAdd ('e: '.$e);
        return $e;
    }

    public function _on_pageIdentifyAfter ( ) {
        $this->httpE                    = $this->_error ();
        $this->page->diagnosticAdd ('Setting page error: "'.$this->httpE.'"');
        $this->page->httpE              = $this->httpE;
        $this->page->httpHeader         = $this->httpHeader;
    }

    public function _on_pageScriptBefore ( ) {
        if (!$this->httpE) {
            return SWEF_BOOL_TRUE;
        }
        $this->page->diagnosticAdd ('Got page error');
        if ($this->httpE==SWEF_HTTP_STATUS_CODE_403) {
            $error                  = SWEF_HTTP_STATUS_CODE_403.SWEF_STR__SPACE.$this->config[sweferror_403];
            $message                = $this->config[sweferror_403_message];
            $template               = $this->config[sweferror_403_template];
            $content_type           = $this->config[sweferror_403_content_type];
        }
        else {
            $error                  = SWEF_HTTP_STATUS_CODE_404.SWEF_STR__SPACE.$this->config[sweferror_404];
            $message                = $this->config[sweferror_404_message];
            $template               = $this->config[sweferror_404_template];
            $content_type           = $this->config[sweferror_404_content_type];
        }
        $this->page->diagnosticAdd ('Error: '.$error);
        if (strlen(trim($message))) {
            $this->notify ($message.': '.$error);
            $this->page->diagnosticAdd ('Sent notification');
        }
        else {
            $this->page->diagnosticAdd ('Notifications NOT MADE');
        }
        $this->page->template   = array (
            SWEF_COL_TEMPLATE       => $template
           ,SWEF_COL_CONTENTTYPE    => $content_type
        );
        $this->page->diagnosticAdd ('Template set to '.$template);
        return SWEF_BOOL_FALSE;
    }

    public function _on_endpointIdentifyAfter ( ) {
        $this->e                        = $this->_error ();
        $this->page->diagnosticAdd ('Endpoint error: "'.$this->e.'"');
    }

    public function _on_headersBefore ( ) {
        if ($this->httpE==SWEF_HTTP_STATUS_CODE_403) {
            $this->httpHeader           = SWEF_HTTP_STATUS_CODE_403.SWEF_STR__SPACE.$this->config[sweferror_403];
        }
        elseif ($this->httpE==SWEF_HTTP_STATUS_CODE_404) {
            $this->httpHeader           = SWEF_HTTP_STATUS_CODE_404.SWEF_STR__SPACE.$this->config[sweferror_404];
        }
        $this->page->diagnosticAdd ('httpHeader: "'.$this->httpHeader.'"');
        header ($this->httpHeader);
        return SWEF_BOOL_TRUE;
    }


    public function _on_endpointScriptBefore ( ) {
        if (!$this->e) {
            return SWEF_BOOL_TRUE;
        }
        $this->page->diagnosticAdd ('Got endpoint error');
        if ($this->e==SWEF_HTTP_STATUS_CODE_403) {
            $e                      = SWEF_HTTP_STATUS_CODE_403;
        }
        else {
            $e                      = SWEF_HTTP_STATUS_CODE_404;
        }
        $c                          = $this->getLiveEndpoint();
        $c->diagnosticAdd ('Error: '.$e);
        if ($this->httpE) {
            return SWEF_BOOL_FALSE;
if ($this->e) {
"ERROR FOR PAGE<br/>\r\n";
}
        }
if ($this->e) {
"ERROR FOR ENDPOINT=".$this->getLiveEndpoint()->endpoint."<br/>\r\n";
}
        $this->page->diagnosticAdd ('Inserting endpoint template '.sweferror_file_endpoint);
        require sweferror_file_endpoint;
        return SWEF_BOOL_FALSE;
    }


    public function _on_endpointTemplateBefore ( ) {
        if ($this->e) {
            return SWEF_BOOL_FALSE;
        }
        return SWEF_BOOL_TRUE;
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
