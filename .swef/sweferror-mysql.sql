
-- SEWF PLUGIN REGISTRATION --

INSERT IGNORE INTO `swef_config_plugin`
    (
      `plugin_Dash_Allow`, `plugin_Dash_Usergroup_Preg_Match`, `plugin_Enabled`,
	  `plugin_Context_LIKE`, `plugin_Classname`, `plugin_Handle_Priority`,
	  `plugin_Configs`
	)
  VALUES
    (
      1, '<^(sysadmin)$>', 1, 'dashboard', '\\Swef\\SwefError', 0,
      '403::Access denied;;\r\n403_message::;;\r\n403_template::/html/dashboard.login.html;;\r\n403_content_type::text/plain; charset=UTF-8;;\r\n404::Not found;;\r\n404_message::Page/resource was not identified;;\r\n404_template::/html/dashboard.default.html;;\r\n404_content_type::text/plain; charset=UTF-8;;\r\n'
    ),
    (
      0, '', 1, 'www-%', '\\Swef\\SwefError', 0,
      '403::Access denied;;\r\n403_message::You must be logged in to view this content;;\r\n403_template::/html/www.default.html;;\r\n403_content_type::text/plain; charset=UTF-8;;\r\n404::Not found;;\r\n404_message::Sorry - page could not be found;;\r\n404_template::/html/www.default.html;;\r\n404_content_type::text/plain; charset=UTF-8;;\r\n\r\n'
    );
