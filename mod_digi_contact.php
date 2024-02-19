<?php
/**
 * 
 * @version             See field version manifest file
 * @package             See field name manifest file
 * @author				Gregorio Nuti
 * @copyright			See field copyright manifest file
 * @license             GNU General Public License version 2 or later
 * 
 */

// no direct access
defined('_JEXEC') or die;

// define ds variable for joomla 3 compatibility
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

// namespaces
use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Version;
use Joomla\CMS\HTML\HTMLHelper;

// include module helper
include_once dirname(__FILE__).DS.'include'.DS.'functions.php';

// define joomla version
$joomlaVersion = 'unknown';
if (version_compare(JVERSION, "4.0.0", ">=")) {
	$joomlaVersion = '4';
} else {
	$joomlaVersion = '3';
}

// popover
$popover = $params->get('popover_switch', 0);
$popoverTitle = $params->get('popover_title', 'Message');
$popoverContent = $params->get('popover_content', 'Content');
$popoverPosition = $params->get('popover_position', 'top');
$popoverData = '';
if ($joomlaVersion == '4') {
	if ($popover) {
		$popoverData = 'title="'.$popoverTitle.'" data-bs-content="'.$popoverContent.'"';
		$popoverClass = '';
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		$wa->useScript('bootstrap.popover');
		HTMLHelper::_('bootstrap.popover','#digi_contact_'.$module->id, [
			'trigger'   => 'hover focus',
	  		'placement' => $popoverPosition
		]);
	}
} else {
	if ($popover) {
		JHtml::_('bootstrap.popover');
		$popoverClass = 'hasPopover';
		$popoverData = 'data-placement="'.$popoverPosition.'" title="'.$popoverTitle.'" data-content="'.$popoverContent.'"';
	}
}

// bootstrap classes
if ($joomlaVersion == '4') {
	$rowClass = 'row';
	$colClass = 'col-md-';
} else {
	$rowClass = 'row-fluid';
	$colClass = 'span';
}

// alert
if ($joomlaVersion == '4') {
	$colClass = 'col-md-';
	$alertClass = 'fade show alert-dismissible';
	$alertDismmiss = '<button style="color:'.$errorMessageColor.';" type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
} else {
	$colClass = 'span';
	$alertClass = 'fade in';
	$alertDismmiss = '<a style="color:'.$errorMessageColor.';" href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>';
}

// email parameters
$recipient = cleanEmail($params->get('email_recipient', 'info@'.$_SERVER['HTTP_HOST']));
$recipientBcc = cleanEmail($params->get('email_recipient_bcc'));
$fromName = $params->get('from_name', 'Your name');
$fromEmail = cleanEmail($params->get('from_email', 'info@'.$_SERVER['HTTP_HOST']));

// url parameters
$fixed_url = $params->get('fixed_url', true);
$myFixedURL = $params->get('fixed_url_address', '');
$add_anchor_to_url = $params->get('add_anchor_to_url', 0);

// repeat email parameters
$repeat_email_switch = $params->get('repeat_email_switch', 0);
$repeat_email_text = $params->get('repeat_email_text', 'Repeat email');

// name parameters
$nameSwitch = $params->get('name_switch', 1);
$nameText = $params->get('name_text', 'User');

// subject parameters
$subjectSwitch = $params->get('subject_switch', 1);
$subjectText = $params->get('subject_text', 'Message from '.$_SERVER['HTTP_HOST']);

// anti-spam parameters
$enable_anti_spam = $params->get('enable_anti_spam', 1);
$myAntiSpamQuestion = $params->get('anti_spam_q', 'How many eyes has a typical person?');
$myAntiSpamAnswer = $params->get('anti_spam_a', '2');

// captcha
$enable_captcha = $params->get('enable_captcha', 0);

// message parameters
$messageSwitch = $params->get('message_switch', 1);
$messageMinLength = $params->get('message_min_length');

// privacy parameters
$enable_privacy = $params->get('enable_privacy', 0);

// layout parameters
$name_position = $params->get('name_position', 0);
$email_position = $params->get('email_position', 0);
$repeat_email_position = $params->get('repeat_email_position', 0);
$subject_position = $params->get('subject_position', 0);
$anti_spam_position = $params->get('anti_spam_position', 0);
$captcha_position = $params->get('captcha_position', 0);
$message_position = $params->get('message_position', 1);
$message_height = $params->get('message_height', 79).'px';
$privacy_position = $params->get('privacy_position', 1);
$send_position = $params->get('send_position', 1);
$fields_width = $params->get('fields_width', 0);
$labels_position = $params->get('labels_position', 0);
if ($name_position == 0 || 
	$email_position == 0 || 
	$repeat_email_position == 0 || 
	$subject_position == 0 || 
	$anti_spam_position == 0 || 
	$captcha_position == 0 || 
	$message_position == 0 || 
	$privacy_position == 0 || 
	$send_position == 0) {
    $print_left = 1;
}
if ($name_position == 1 || 
	$email_position == 1 || 
	$repeat_email_position == 1 || 
	$subject_position == 1 || 
	$anti_spam_position == 1 || 
	$captcha_position == 1 || 
	$message_position == 1 || 
	$privacy_position == 1 || 
	$send_position == 1) {
    $print_right = 1;
}

// text parameters
$myNameLabel = $params->get('name_label', 'Name:');
$myEmailLabel = $params->get('email_label', 'Email:');
$myRepeatEmailLabel = $params->get('repeat_email_label', 'Email:');
$mySubjectLabel = $params->get('subject_label', 'Subject:');
$myMessageLabel = $params->get('message_label', 'Message:');
$privacyLabel = $params->get('privacy_label', 'I agree with Terms and Conditions');
$privacyLink = $params->get('privacy_link');
$buttonText = $params->get('button_text', 'Send Message');
$mandatoryMark = $params->get('mandatory_mark') ? ' '.$params->get('mandatory_mark') : '';
$pageText = $params->get('page_text', 'Thank you for your contact.');
$errorText = $params->get('error_text', 'Your message could not be sent. Please try again.');
$noEmail = $params->get('no_email', 'Please write your email.');
$invalidEmail = $params->get('invalid_email', 'Please write a valid email.');
$invalid_repeat_email = $params->get('invalid_repeat_email', 'Emails do not match.');
$noName = $params->get('no_name', 'Please write your name.');
$noSubject = $params->get('no_subject', 'Please write the subject.');
$noMessage = $params->get('no_message', 'Please write a message.');
$shortMessage = $params->get('short_message', 'The message is too short. Characters needed:');
$noPrivacy = $params->get('no_privacy', 'You must agree our Terms and Conditions.');
$missingField = $params->get('missing_field', 'Please write a message.');
$wrongantispamanswer = $params->get('wrong_antispam', 'Wrong anti-spam answer.');
$wrongcaptcha = $params->get('wrong_captcha', 'Wrong captcha.');
$otherFields = $params->get('other-fields-text');
$preText = $params->get('pre_text');
$preTextHR = $params->get('pre_text_hr');
$postText = $params->get('post_text');
$postTextHR = $params->get('post_text_hr');
$customMessage = $params->get('custom-message-text');

// style parameters
$import_css = $params->get('import_css', 0);
$confirmMessageBackgroundColor = $params->get('confirm-custom-background-color', '#dff0d8');
$confirmMessageColor = $params->get('confirm-custom-color', '#468847');
$confirmMessageBorderColor = $params->get('confirm-custom-border-color', '#d6e9c6');
$errorMessageBackgroundColor = $params->get('error-custom-background-color', '#f2dede');
$errorMessageColor = $params->get('error-custom-color', '#b94a48');
$errorMessageBorderColor = $params->get('error-custom-border-color', '#eed3d7');
$sendClass = $params->get('send-class', 'btn btn-default');

// css inclusion
if ($import_css) {
	$document= JFactory::getDocument();
	$document->addStyleSheet(Uri::root().'modules/mod_digi_contact/assets/css/style.css');
}

// module class suffix parameter
$mod_class_suffix = $params->get('moduleclass_sfx', '');

// url redirection
if ($fixed_url) {
	// url is fixed and defined by field
	$url = $myFixedURL;
} else {
	// url is the same of the request
	$url = JUri::getInstance();
}

// add anchor on redirection url to come back in the same position on destination page
if ($add_anchor_to_url) {
	$url .= '#digi_contact_'.$module->id;
}

$url = htmlentities($url, ENT_COMPAT, "UTF-8");

$myError = '';
$CORRECT_ANTISPAM_ANSWER = '';
$CORRECT_NAME = '';
$CORRECT_EMAIL = '';
$CORRECT_REPEAT_EMAIL = '';
$CORRECT_SUBJECT = '';
$CORRECT_MESSAGE = '';

// include layout
require JModuleHelper::getLayoutPath('mod_digi_contact', $params->get('layout', 'default'));

?>