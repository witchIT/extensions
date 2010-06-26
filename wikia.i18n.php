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
	'mw-specialpagesgroup-od' => "Organic Design",
	'od-nospec' => "There is no specification yet, you can create one after submitting this form",
	'od-nospec-form' => "There is no specification yet, you can create one after submitting this form. <br />This should lead to a complete set of Milestone Tasks.",
	'od-nospec-template' => "By clicking on the Specifications link below you can create a complete set of Milestone Tasks and their Due Dates",
	'od-nofaq' => "After you submit this form, write Position Description, FAQ, Best Practice and Contract articles for this Role",
	'od-multiselect-click' => "Ctrl-click to multi-select",
	'od-knowledge' => "Includes: Explicit information requirements for the job e.g. product or process specifications, orders, priorities, records, QC records, other organisational documentation needs etc. Example: Familiarity with telephone system, Skype, Microsoft Word, Microsoft Excel, Back Office protocols etc.",
	'od-resources' => "Includes: Specific tools or resources needed to carry out the task.",
	'od-hazard' => "Includes: Hazards of the job and how to mitigate them. Example: Workstation to be set up so as to ensure proper posture etc. Keyboard set at correct height, screen at eye level, good lighting, electrical surge protectors on equipment, staff to take stretch breaks every (x) hours or minutes.",
	'od-quality' => "Includes: Measures taken to ensure quality service and customer satisfaction. Example: Quality assurance measures for the job, including critical control points e.g. customer satisfaction, follow up procedures, timely delivery of products, complaints process, etc.",
	'od-sop' => "Includes: Steps taken to complete the task - could refer to operational manuals or flow charts. Throughput requirements for the job (how fast should the job be done, what are the time constraints/ limits for the job).",
	'od-contingency' => "Includes: Contingencies (how to deal with events when they do not go according to plan).", 
	'od-description-clear' => "Make the description clear, assuming no prior context, even if raising it to yourself",
	'od-datepicker-use' => "Use Datepicker to select Date",
	'od-minform-record-list' => "*Document
*Procedure
*Project
*Organisation
*Role
*Task
*Activity",
	'od-calendar-use' => "Use Datepicker, then Go to your Calendar",
	'od-calendar-form' => "Use Datepicker, then Submit or Search for Date",
	'od-calendar-another' => "Use Datepicker, then Go to another Date",
	'od-textbox-alias' => "Other addresses that point to this address. Write every entry on a new line.",
	'od-textbox-forward' => "Other addresses that emails are forwarded to. Write every entry on a new line.",
	'od-sender-explain' => "Use \"Sender\" and \"Recipient\" for transactions, and where activities are not person-to-organisation.",
	'od-invoice-status-list' => "*Drafted
*Sent
*Paid",
	'od-invoice-status-list-info' => "The list of current states that an invoice in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-invoice-status-list|action=edit}} here]",
	'od-person-title-list' => "*Mr
*Mrs
*Miss
*Ms
*Dr",
	'od-person-title-list-info' => "This is the list of different titles that a person in the organisation can have. To change the options available in this list click [{{fullurl:MediaWiki:od-person-title-list|action=edit}} here]",
	'od-person-interests' => "Philosophy & Spirituality
*Organic Design
*Financial system
*Sovereignty
*Free energy
*Software development
*Science & Technology",
	'od-person-interests-info' => "These options are used to determine the recipients in mailouts, to change the options available in this list click [{{fullurl:MediaWiki:od-person-interests|action=edit}} here]",
	'od-person-location-list' => "*Home
*Office
*Factory",
	'od-person-location-list-info' => "This is the list of locations that people in the organisation can work from. To change the options available in this list click [{{fullurl:MediaWiki:od-person-location-list|action=edit}} here]",
	'od-person-administration-info' => "{{warning|This section is only viewable by system administrators because it defines peoples roles and access rights.}}",
	'od-person-external-info' => "External contributors can access nothing by default, they must be granted specific access to articles to be able to view them.",
	'od-procedure-status-list' => "*Planned
*Work in Progress
*Final Draft
*Completed
*Signed Off
*Invoiced
*Paid
*Cancelled",
	'od-procedure-status-list-info' => "The list of current states that a procedure in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-procedure-status-list|action=edit}} here]",
	'od-procedure-priority-list' => "*1 - Urgent
*2 - High
*3 - Standard
*4 - Low",
	'od-procedure-priority-list-info' => "The list of different priorities that a procedure in the organisation can have. To change the options available in this list click [{{fullurl:MediaWiki:od-procedure-priority-list|action=edit}} here]",
	'od-procedure-version-list' => "*New
*1.0
*2.0
*3.0",
	'od-procedure-version-list-info' => "The list of different versions that a procedure in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-procedure-version-list|action=edit}} here]",
	'od-project-type-list' => "*Specification
*Development
*Implementation
*Research
*Consultancy
*Documentation
*Systems Administration",
	'od-project-type-list-info' => "The list of different types that a project in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-project-type-list|action=edit}} here]",
	'od-project-status-list' => "*Planned
*Proposal
*Specification
*Work in Progress
*Completed
*On hold
*Cancelled",
	'od-project-status-list-info' => "The list of current states that a project in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-project-status-list|action=edit}} here]",
	'od-task-budgettype-list' => "*Hours
*NZD
*USD
*EUR",
	'od-task-budgettype-list-info' => "The list of currencies for the task\'s budget. To change the options available in this list click [{{fullurl:MediaWiki:od-task-budgettype-list|action=edit}} here]",
	'od-task-type-list' => "*Request
*Inquiry
*Problem
*Task
*Regular Task
*Ongoing Task
*Procedure
*Milestone",
	'od-task-type-list-info' => "The list of different types that a task in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-task-type-list|action=edit}} here]",
	'od-task-status-list' => "*Planned
*Assigned
*In Progress
*Completed
*Resolved
*Invoiced
*Paid
*Postponed
*Cancelled",
	'od-task-status-list-info' => "The list of current states that a task in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-task-status-list|action=edit}} here]",
	'od-task-priority-list' => "*1 - Urgent
*2 - High
*3 - Medium
*4 - Low",
	'od-task-priority-list-info' => "The list of different priorities that a task in the organisation can have. To change the options available in this list click [{{fullurl:MediaWiki:od-task-priority-list|action=edit}} here]",
	'od-activity-currency-list' => "*NZD
*EUR
*USD
*CHF",
	'od-activity-currency-list-info' => "The list of currencies for the activity\'s budget. To change the options available in this list click [{{fullurl:MediaWiki:od-activity-currency-list|action=edit}} here]",
	'od-activity-type-list' => "*Work
*Procedure
*Meeting
*Weekly Meeting
*Discussion
*Travel",
	'od-activity-type-list-info' => "The list of different types that an activity in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-activity-type-list|action=edit}} here]",
	'od-activity-status-list' => "*Planned
*Work in Progress
*Final Draft
*Completed
*Signed Off
*Invoiced
*Paid
*Cancelled",
	'od-activity-status-list-info' => "The list of current states that an activity in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-activity-status-list|action=edit}} here]",
	'od-transaction-currency-list' => "*NZD
*EUR
*USD
*CHF",
	'od-transaction-currency-list-info' => "The list of currencies for the transaction. To change the options available in this list click [{{fullurl:MediaWiki:od-transaction-currency-list|action=edit}} here]",
	'od-transaction-type-list' => "*Standard
*Other",
	'od-transaction-type-list-info' => "The list of different types that a transaction in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-transaction-type-list|action=edit}} here]",
	'od-role-smb-list' => "*management
*staff
*users",
	'od-role-smb-list-info' => "The list of shares that a role in the organisation can have. To change the options available in this list click [{{fullurl:MediaWiki:od-role-smb-list|action=edit}} here]",
	'od-document-type-list' => "*Specification
*Development
*Consultancy
*Memo
*Letter
*Prospectus",
	'od-document-type-list-info' => "The list of different types that a document in the organisation can be. To change the options available in this list click [{{fullurl:MediaWiki:od-document-type-list|action=edit}} here]",
	'od-document-status-list' => "*Work in Progress
*Completed
*Signed Off",
	'od-document-status-list-info' => "The list of current states that a document in the organisation can be in. To change the options available in this list click [{{fullurl:MediaWiki:od-document-status-list|action=edit}} here]",
	'od-document-category-list' => "*Directors
*Management
*Sales
*Factory
*R&D
*Customer Services",
	'od-document-category-list-info' => "The list of categories that a document in the organisation can be assigned to. To change the options available in this list click [{{fullurl:MediaWiki:od-document-category-list|action=edit}} here]",
	'od-document-edit-info' => "To edit this document, click the \"edit\" link above as usual and then add the content below the existing properties infomration.",
	'od-sidebar' => "{{#ifgroup:user|{{#tree:id=sidebartree|root=<big>'''{{ns:4}}'''</big>|
*[[{{CURRENTPERSON}}|My Home Page]]
*[[Main Page]]
*[[Special:Recentchanges|Recent changes]]
*[[Best Practices]]
*[[Workgroup Training]]
*[[Help]]
*[[Cheatsheet]]
*[[Sandbox]]
*'''Categories'''
**[[:Category:People|People]]   [{{fullurl:Special:UserLogin|type=signup}} <small>&#91;create&#93;</small>]
**[[:Category:Departments|Departments]] {{NewRecordLinkSmall|Department}}
**[[:Category:Roles|Roles]] {{NewRecordLinkSmall|Role}}
**[[:Category:Projects|Projects]] {{NewRecordLinkSmall|Project}}
**[[:Category:Organisations|Organisations]] {{NewRecordLinkSmall|Organisation}}
**[[:Category:Documents|Documents]] {{NewRecordLinkSmall|Document}}
**[[:Category:Procedures|Procedures]] {{NewRecordLinkSmall|Procedure}}
**[[:Category:Computers|Computers]] {{NewRecordLinkSmall|Computer}}
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
}}}}",
	'footer' => "[[OD:Wiki organisation|About Wiki Organisation]]",
);
