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
	'od-calendar-use' => 'Use Datepicker, then Go to your Calendar',
	'od-calendar-form' => 'Use Datepicker, then Submit or Search for Date',
	'od-calendar-another' => 'Use Datepicker, then Go to another Date',
	'od-textbox-alias' => 'Other addresses that point to this address. Write every entry on a new line.',
	'od-textbox-forward' => 'Other addresses that emails are forwarded to. Write every entry on a new line.',
	'od-person-title-list' => '<option>Mr</option><option>Mrs</option><option>Miss</option><option>Ms</option><option>Dr</option>',
	'od-activity-status-list' => '<option>Planned</option><option>Work in Progress</option><option>Final Draft</option><option>Completed</option><option>Signed Off</option><option>Invoiced</option><option>Paid</option><option>Cancelled</option>',
	'od-document-status-list' => '<option>Work in Progress</option><option>Completed</option><option>Signed Off Download Ready</option>',
	'od-person-location-list' => '<option>Head Office</option><option>Orini Factory</option><option>Auckland Sales Office</option>',
	'od-procedure-status-list' => '<option>Planned</option><option>Work in Progress</option><option>Final Draft</option><option>Completed</option> <option>Signed Off</option><option>Invoiced</option><option>Paid</option><option>Cancelled</option>',
	'od-project-type-list' => '<option>Specification</option><option>Development</option><option>Consultancy</option><option>Documentation</option><option>Content</option><option>Systems Administration</option>',
	'od-task-budgettype-list' => '<option>Hours</option><option>NZD</option><option>USD</option><option>EUR</option>',
	'od-task-type-list' => '<option>Request</option><option>Inquiry</option><option>Problem</option><option>Task</option><option>Regular Task</option><option>Ongoing Task</option><option>Procedure</option><option>Milestone</option>',
	'od-activity-type-list' => '<option>Work</option><option>Procedure</option><option>Meeting</option><option>Weekly Meeting</option><option>Discussion</option><option>Travel</option>',
	'od-document-type-list' => '<option>Specification</option><option>Development</option><option>Consultancy</option><option>Memo</option><option>Letter</option><option>Prospectus</option>',
	'od-person-title-list' => '<option>Mr</option><option>Mrs</option><option>Miss</option><option>Ms</option><option>Dr</option>',
	'od-procedure-version-list' => '<option>New</option><option>1.0</option><option>2.0</option><option>3.0</option>',
	'od-role-group-list' => '<option>Adminstrator</option><option>Moderator</option><option>Editor</option><option>Developer</option><option>User</option>',
	'od-task-priority-list' => '<option>1 - Urgent</option><option>2 - High</option><option>3 - Medium</option><option>4 - Low</option>',
	'od-activity-currency-list' => '<option>NZD</option><option>EUR</option><option>USD</option><option>CHF</option>',
	'od-minform-record-list' => '<option>Activity</option><option>Task</option><option>Document</option><option>Procedure</option><option>Date</option>',
	'od-procedure-priority-list' => '<option>1 - Urgent</option> <option>2 - High</option><option>3 - Standard</option><option>4 - Low</option>',
	'od-project-status-list' => '<option>Planned</option><option>Work in Progress</option><option>Final Draft</option><option>Completed</option><option>Signed Off</option><option>Invoiced</option><option>Paid</option><option>Cancelled</option>',
	'od-role-smb-list' => '<option>earthwise-management</option><option>earthwise-staff</option><option>kiwigreen-management</option><option>kiwigreen-staff</option><option>shared-management</option>',
	'od-task-status-list' => '<option>Planned</option><option>Assigned</option><option>In Progress</option><option>Resolved</option> <option>Invoiced</option> <option>Paid</option><option>Postponed</option><option>Cancelled</option>',
	'sidebar' => "{{#tree:id=side-bar-tree|openlevels=1|root=<big>'''&nbsp;{{ns:4}}&nbsp;&nbsp;&nbsp;&nbsp;'''</big>|
*[[{{CURRENTPERSON}}|My Home Page]]
*[[Main Page]]
*[[Special:Recentchanges|Recent changes]]
*[[Best Practices]]
*[[Workgroup Training]]
*[[Help]]
*[[Cheatsheet]]
*[[Sandbox]]
*'''Categories'''
**[[:Category:People|People]] &nbsp; [{{fullurl:Special:UserLogin|type=signup}} <small>&#91;create&#93;</small>]
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
**[{{fullurl:MediaWiki:Sidebar|action=edit}} Edit Sidebar]
}}"
);
