<?php
/**
 * Internationalisation for OrganicDesign wiki shared messages
 *
 * @author Nad
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Nad
 */
$messages['en'] = array(
	'od-nospec' => 'There is no specification yet, you can create one after submitting this form',
	'od-nospec-form' => 'There is no specification yet, you can create one after submitting this form. <br />This should lead to a complete set of Milestone Tasks.',
	'od-nospec-template' => 'By clicking on the Specifications link below you can create a complete set of Milestone Tasks and their Due Dates',
	'od-nofaq' => 'After you submit this form, write Position Description, FAQ, Best Practice and Contract articles for this Role',
	'od-multiselect-click' => 'Ctrl-click to multi-select',
	'od-knowledge' => 'Includes: Explicit information requirements for the job e.g. product or process specifications, orders, priorities, records, QC records, other organisational documentation needs etc. Example: Familiarity with telephone system, Skype, Microsoft Word, Microsoft Excel, Back Office protocols etc.',
	'od-resources' => 'Includes: Specific tools or resources needed to carry out the task.',
	'od-hazard' => 'Includes: Hazards of the job and how to mitigate them. Example: Workstation to be set up so as to ensure proper posture etc. Keyboard set at correct height, screen at eye level, good lighting, electrical surge protectors on equipment, staff to take stretch breaks every (x) hours or minutes.',
	'od-quality' => 'Includes: Measures taken to ensure quality service and customer satisfaction. Example: Quality assurance measures for the job, including critical control points e.g. customer satisfaction, follow up procedures, timely delivery of products, complaints process, etc.',
	'od-sop' => 'Includes: Steps taken to complete the task - could refer to operational manuals or flow charts. Throughput requirements for the job (how fast should the job be done, what are the time constraints/ limits for the job).',
	'od-contingency' => 'Includes: Contingencies (how to deal with events when they do not go according to plan).', 
	'od-description-clear' => 'Make the description clear, assuming no prior context, even if raising it to yourself',
	'od-datepicker-use' => 'Use Datepicker to select Date',
	'od-minform-record-list' => '<option>Activity</option><option>Task</option><option>Project</option><option>Document</option><option>Procedure</option><option>Date</option>',
	'od-calendar-use' => 'Use Datepicker, then Go to your Calendar',
	'od-calendar-form' => 'Use Datepicker, then Submit or Search for Date',
	'od-calendar-another' => 'Use Datepicker, then Go to another Date',
	'od-textbox-alias' => 'Other addresses that point to this address. Write every entry on a new line.',
	'od-textbox-forward' => 'Other addresses that emails are forwarded to. Write every entry on a new line.',
	'od-person-title-list' => '<option>Mr</option><option>Mrs</option><option>Miss</option><option>Ms</option><option>Dr</option>',
	'od-person-title-list-info' => 'This is the list of different titles that a person in the organisation can have. To change the options available in this list click [{{fullurl:MediaWiki:od-person-title-list|action=edit}} here]',
	'od-person-location-list' => '<option>Head Office</option><option>Orini Factory</option><option>Auckland Sales Office</option>',
	'od-person-location-list-info' => 'This is the list of locations that people in the organisation can work from. To change the options available in this list click [{{fullurl:MediaWiki:od-person-location-list|action=edit}} here]',
	'od-person-administration-info' => '{{warning|This section is only viewable by system administrators because it defines peoples roles and access rights.}}',
	'od-person-external-info' => 'External contributors can access nothing by default, they must be granted specific access to articles to be able to view them.',
	'od-procedure-status-list' => '<option>Planned</option><option>Work in Progress</option><option>Final Draft</option><option>Completed</option> <option>Signed Off</option><option>Invoiced</option><option>Paid</option><option>Cancelled</option>',
	'od-procedure-status-list-info' => 'The list of current states that a procedure in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-procedure-status-list|action=edit}} here]',
	'od-procedure-priority-list' => '<option>1 - Urgent</option> <option>2 - High</option><option>3 - Standard</option><option>4 - Low</option>',
	'od-procedure-priority-list-info' => 'The list of different priorities that a procedure in the organisation can have. To change the options available in this list click [{{fullurl:MediaWiki:od-procedure-priority-list|action=edit}} here]',
	'od-procedure-version-list' => '<option>New</option><option>1.0</option><option>2.0</option><option>3.0</option>',
	'od-procedure-version-list-info' => 'The list of different versions that a procedure in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-procedure-version-list|action=edit}} here]',
	'od-project-type-list' => '<option>Specification</option><option>Development</option><option>Consultancy</option><option>Documentation</option><option>Content</option><option>Systems Administration</option>',
	'od-project-type-list-info' => 'The list of different types that a project in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-project-type-list|action=edit}} here]',
	'od-project-status-list' => '<option>Planned</option><option>Work in Progress</option><option>Final Draft</option><option>Completed</option><option>Signed Off</option><option>Invoiced</option><option>Paid</option><option>Cancelled</option>',
	'od-project-status-list-info' => 'The list of current states that a project in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-project-status-list|action=edit}} here]',
	'od-task-budgettype-list' => '<option>Hours</option><option>NZD</option><option>USD</option><option>EUR</option>',
	'od-task-budgettype-list-info' => 'The list of currencies for the task\'s budget. To change the options available in this list click [{{fullurl:MediaWiki:od-task-budgettype-list|action=edit}} here]',
	'od-task-type-list' => '<option>Request</option><option>Inquiry</option><option>Problem</option><option>Task</option><option>Regular Task</option><option>Ongoing Task</option><option>Procedure</option><option>Milestone</option>',
	'od-task-type-list-info' => 'The list of different types that a task in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-task-type-list|action=edit}} here]',
	'od-task-status-list' => '<option>Planned</option><option>Assigned</option><option>In Progress</option><option>Resolved</option> <option>Invoiced</option> <option>Paid</option><option>Postponed</option><option>Cancelled</option>',
	'od-task-status-list-info' => 'The list of current states that a task in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-task-status-list|action=edit}} here]',
	'od-task-priority-list' => '<option>1 - Urgent</option><option>2 - High</option><option>3 - Medium</option><option>4 - Low</option>',
	'od-task-priority-list-info' => 'The list of different priorities that a task in the organisation can have. To change the options available in this list click [{{fullurl:MediaWiki:od-task-priority-list|action=edit}} here]',
	'od-activity-currency-list' => '<option>NZD</option><option>EUR</option><option>USD</option><option>CHF</option>',
	'od-activity-currency-list-info' => 'The list of currencies for the activity\'s budget. To change the options available in this list click [{{fullurl:MediaWiki:od-activity-currency-list|action=edit}} here]',
	'od-activity-type-list' => '<option>Work</option><option>Procedure</option><option>Meeting</option><option>Weekly Meeting</option><option>Discussion</option><option>Travel</option>',
	'od-activity-type-list-info' => 'The list of different types that an activity in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-activity-type-list|action=edit}} here]',
	'od-activity-status-list' => '<option>Planned</option><option>Work in Progress</option><option>Final Draft</option><option>Completed</option><option>Signed Off</option><option>Invoiced</option><option>Paid</option><option>Cancelled</option>',
	'od-activity-status-list-info' => 'The list of current states that an activity in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-activity-status-list|action=edit}} here]',
	'od-role-group-list' => '<option>Adminstrator</option><option>Moderator</option><option>Editor</option><option>Developer</option><option>User</option>',
	'od-role-group-list-info' => 'The list of groups that a role in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-role-group-list|action=edit}} here]',
	'od-role-smb-list' => '<option>earthwise-management</option><option>earthwise-staff</option><option>kiwigreen-management</option><option>kiwigreen-staff</option><option>shared-management</option>',
	'od-role-smb-list-info' => 'The list of shares that a role in the organisation can have. To change the options available in this list click [{{fullurl:MediaWiki:od-role-smb-list|action=edit}} here]',
	'od-document-type-list' => '<option>Specification</option><option>Development</option><option>Consultancy</option><option>Memo</option><option>Letter</option><option>Prospectus</option>',
	'od-document-type-list-info' => 'The list of different types that a document in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-document-type-list|action=edit}} here]',
	'od-document-status-list' => '<option>Work in Progress</option><option>Completed</option><option>Signed Off</option>',
	'od-document-status-list-info' => 'The list of current states that a document in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-document-status-list|action=edit}} here]',
	'od-document-category-list' => '<option>Directors</option><option>Management</option><option>Sales</option><option>Factory</option><option>R&D</option><option>Customer Services</option>',
	'od-document-category-list-info' => 'The list of categories that a document in the organisation can be assigned to. To change the options available in this list click [{{fullurl:MediaWiki:od-document-category-list|action=edit}} here]',
	'sidebar' => "{{#tree:id=side-bar-tree|openlevels=1|root=<big>''' {{ns:4}}    '''</big>|
*[[{{CURRENTPERSON}}|My Home Page]]
*[[Main Page]]
*[[Special:Recentchanges|Recent changes]]
*[[Best Practices]]
*[[Workgroup Training]]
*[[Help]]
*[[Cheatsheet]]
*[[Sandbox]]
*'''Categories'''
**[[:Category:People|People]]   [{{fullurl:Special:UserLogin|type=signup}} <small>&#91;create&#93;</small>]https://ewg.organicdesign.co.nz/Special:RecordAdmin/Procedure
**[[:Category:Roles|Roles]] {{NewRecordLinkSmall|Role}}
**[[:Category:Documents|Documents]] {{NewRecordLinkSmall|Document}}
**[[:Category:Procedures|Procedures]] {{NewRecordLinkSmall|Procedure}}
**[[:Category:Projects|Projects]] {{NewRecordLinkSmall|Project}}
**[[:Category:Tasks|Tasks]] {{NewRecordLinkSmall|Task}}
**[[:Category:Activities|Activities]] {{NewRecordLinkSmall|Activity}}
**[[Special:RecordAdmin|Advanced search...]]
**[[Special:Categories|All categories...]]
*'''Recent Activity'''
**{{RecentActivity:type=edits|count=10|format=**}}
*'''Toolbox'''
**[{{fullurl:Special:Whatlinkshere|target={{FULLPAGENAMEE}}}} What links here]
**[[Special:Upload|Upload file]]
***[[Special:NewFiles|View uploaded files]]
***[[Special:ListFiles|Detailed file list]]
**[{{fullurl:{{FULLPAGENAMEE}}|action=pdfbook&format=single}} Print to PDF]
**[[Special:Specialpages|Special pages]]
**[{{fullurl:Special:Allpages|namespace=8}} Custom messages]
**[{{fullurl:MediaWiki:Sidebar|action=edit}} Edit Sidebar]
}}"
);
