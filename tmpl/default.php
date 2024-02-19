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

// check if the submission has been requested from this module instance and if recipient email has been sent
if ($_POST['action'] == 'digi_contact_'.$module->id.'_action' && isset($_POST["digi_contact_email"])) {
	$CORRECT_SUBJECT = htmlentities($_POST["digi_contact_subject"], ENT_COMPAT, "UTF-8");
	$CORRECT_MESSAGE = htmlentities($_POST["digi_contact_message"], ENT_COMPAT, "UTF-8");
    
	// check all mandatory fields in the form

	// check privacy
	if ($enable_privacy) {
		if ($_POST["digi_contact_privacy_check"] != 'on') {
    		$myError = $noPrivacy;
    	}
  	}
	
	// check anti-spam
	if ($enable_anti_spam) {
		if ($_POST["digi_contact_anti_spam_answer"] != $myAntiSpamAnswer) {
    		$myError = $wrongantispamanswer;
    	} else {
      		$CORRECT_ANTISPAM_ANSWER = htmlentities($_POST["digi_contact_anti_spam_answer"], ENT_COMPAT, "UTF-8");
    	}
  	}
  	
  	// check captcha
	if ($enable_captcha) {
		if ($_POST["g-recaptcha-response"] == '') {
    		$myError = $wrongcaptcha;
    	}
  	}
	
	// check name
    if ($_POST["digi_contact_name"] == "" && $nameSwitch == 1) {
        $myError = $noName;
    }
    
    // check subject
    if ($_POST["digi_contact_subject"] == "" && $subjectSwitch == 1) {
        $myError = $noSubject;
    }
    
    // check message
    if ($_POST["digi_contact_message"] == "" && $messageSwitch == 1) {
        $myError = $noMessage;
    }
    
    // check message length
  	if ($messageMinLength && $_POST["digi_contact_message"] && strlen($_POST["digi_contact_message"]) < $messageMinLength) {
      	$myError = $shortMessage.' '.$messageMinLength;
    }
  
	// check email
	if ($_POST["digi_contact_email"] == "") {
		$myError = $noEmail;
	}
  	if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/", strtolower($_POST["digi_contact_email"]))) {
    	$myError = $invalidEmail;
  	} else {
    	$CORRECT_EMAIL = htmlentities($_POST["digi_contact_email"], ENT_COMPAT, "UTF-8");
  	}
  	
  	// check repeat email
	if ($_POST["digi_contact_repeat_email"] == "" && $repeat_email_switch == 1) {
		$myError = $noEmail;
	}
  	if ((strtolower($_POST["digi_contact_repeat_email"]) != strtolower($_POST["digi_contact_email"])) && $repeat_email_switch == 1) {
    	$myError = $invalid_repeat_email;
  	} else if ($repeat_email_switch == 1) {
    	$CORRECT_REPEAT_EMAIL = htmlentities($_POST["digi_contact_repeat_email"], ENT_COMPAT, "UTF-8");
  	}
  	
  	// check other fields
	if ($otherFields) {
		$otherFieldsLeft = preg_split('/\r\n|\r|\n/', $otherFields);
		foreach ($otherFieldsLeft as $field) {
			$fieldValues = explode(',', $field);
			if (count($fieldValues) >= 4) {
				$value = 'digi_contact_'.$fieldValues[1];
				$mandatory = $fieldValues[3];
				if ($mandatory == 'yes') {
					if ($_POST[$value] == "") {
						$myError = $missingField;
					}
				}
			}
		}
	}
	
  	if ($myError == '') {
  		
  		$myEmail = $_POST["digi_contact_email"];
  		
  		if ($subjectSwitch == 1) {
    		$mySubject = $_POST["digi_contact_subject"];
    	} else {
    		$mySubject = $subjectText;
    	}
    	
    	if ($nameSwitch == 1) {
    		$myName = $_POST["digi_contact_name"];
		} else {
			$myName = $nameText;
		}
    	
    	if ($customMessage) {
    		
    		// replace variables in custom message
    		$customMessage = str_replace("{email}", $myEmail, $customMessage);
    		$customMessage = str_replace("{name}", $myName, $customMessage);
    		$customMessage = str_replace("{subject}", $mySubject, $customMessage);
    		$customMessage = str_replace("{message}", $_POST["digi_contact_message"], $customMessage);
    		$customMessage = str_replace("{siteurl}", JUri::base(), $customMessage);
    		$customMessage = str_replace("{pageurl}", JUri::getInstance(), $customMessage);
    		$customMessage = str_replace("{sitename}", JFactory::getConfig()->get("sitename"), $customMessage);
    		$customMessage = str_replace("{newline}", "\n\n", $customMessage);
    		
    		// other fields
			if ($otherFields) {
				$otherFieldsArray = preg_split('/\r\n|\r|\n/', $otherFields);
				foreach ($otherFieldsArray as $field) {
					$fieldValues = explode(',', $field);
					$tag = '{'.$fieldValues[1].'}';
					$value = 'digi_contact_'.$fieldValues[1];
					$customMessage = str_replace($tag, $_POST[$value], $customMessage);
				}
			}
    		
    		$myMessage = $customMessage;
		} else {
			
			// set the message
			$myMessage = "You received a message from ". $myName ." and you can reply to ". $myEmail .".";
			if ($messageSwitch == 1) {
				$myMessage .= "\n\n". $_POST["digi_contact_message"];
			}
					
			// other fields
			if ($otherFields) {
				$otherFieldsArray = preg_split('/\r\n|\r|\n/', $otherFields);
				$otherFieldsString = "\n\n";
				foreach ($otherFieldsArray as $field) {
					$fieldValues = explode(',', $field);
					$value = 'digi_contact_'.$fieldValues[1];
					$otherFieldsString .= $fieldValues[0].": ".$_POST[$value].".\n";
				}
				$myMessage .= $otherFieldsString;
			}
		}
		
		// create the mailer
    	$mailSender = JFactory::getMailer();
      
      	// get joomla config
      	$config = JFactory::getConfig();
    	
    	// add main email address to the mailer
    	$mailSender->addRecipient($recipient);
    	
    	// if not empty add bcc email address to the mailer
    	if ($recipientBcc) {
    		$mailSender->addBcc($recipientBcc);
    	}
    	
    	// add sender address and name
      	// if not already set in joomla settings (which overrides the module settings)
      	if ($config->get('mailfrom') == '' && $config->get('fromname') == '') {
    		$mailSender->setSender($fromEmail,$fromName);
        }
        
        // add user address where message recipient can reply
		// if not already set in joomla settings (which overrides the module settings)
      	if ($config->get('replyto') == '') {
        	$mailSender->addReplyTo( $_POST["digi_contact_email"], '' );
        }
		
		// add subject to the mailer
    	$mailSender->setSubject($mySubject);
    	
    	// add message to the mailer
    	$mailSender->setBody($myMessage);
		
    	if ($mailSender->Send() !== true) {
      		$myReplacement = $errorText;
      		print '<div class="alert alert-danger '.$alertClass.'" style="background-color:'.$errorMessageBackgroundColor.'; border-color:'.$errorMessageBorderColor.'; color:'.$errorMessageColor.';">'.$alertDismmiss.$myReplacement.'</div>';
      		return true;
    	} else {
      		$myReplacement = $pageText;
      		print '<div id="digi_contact_'.$module->id.'" class="digi_contact">';
      		print '<div class="alert alert-success '.$alertClass.'" style="background-color:'.$confirmMessageBackgroundColor.'; border-color:'.$confirmMessageBorderColor.'; color:'.$confirmMessageColor.';">'.$alertDismmiss.$myReplacement.'</div>';
      		print '</div>';
      		return true;
    	}
	
  	}
} // end if posted

// check recipient
if ($recipient === "") {
  	$myReplacement = 'No recipient specified';
  	print '<div id="digi_contact_'.$module->id.'" class="digi_contact">';
  	print '<div class="alert alert-success '.$alertClass.'" style="background-color:'.$confirmMessageBackgroundColor.'; border-color:'.$confirmMessageBorderColor.'; color:'.$confirmMessageColor.';">'.$alertDismmiss.$myReplacement.'</div>';
  	print '</div>';
  	return true;
}

print '<div id="digi_contact_'.$module->id.'" class="digi_contact '.$popoverClass.' '.$mod_class_suffix.'" '.$popoverData.'>';
print '<form id="digi_contact_'.$module->id.'_form" action="' . $url . '" method="post">' . "\n";

if ($preText) {
	print '<p id="digi_contact_pre_text" class="lead">'.$preText.'</p>' . "\n";
}

if ($myError != '') {
	print '<div class="alert alert-danger '.$alertClass.'" style="background-color:'.$errorMessageBackgroundColor.'; border-color:'.$errorMessageBorderColor.'; color:'.$errorMessageColor.';">'.$alertDismmiss.$myError.'</div>';
}

if ($preTextHR && !empty($preText)) {
	print '<hr />';
}

//start row-fluid
print '<div class="'.$rowClass.'">';

print ($print_left == 1) ? ($print_right == 1) ? '<div class="'.$colClass.'6">' : '<div class="'.$colClass.'12">' : '';

	// print name
	if ($nameSwitch == 1) {
		if ($name_position == 0) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($name_position == 0) ? '<label class="form-label">'.$myNameLabel.$mandatoryMark.'</label>' : '';
			}
			print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_name" value="'.$CORRECT_NAME.'" placeholder="'.($labels_position == 0 ? $myNameLabel.$mandatoryMark : '').'" />';
			print '</div>'."\n";
		}
	}
	
	// print email
	if ($email_position == 0) {
		print '<div class="mb-3">';
		if ($labels_position == 1) {
			print ($email_position == 0) ? '<label class="form-label">'.$myEmailLabel.$mandatoryMark.'</label>' : '';
		}
		print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_email" value="'.$CORRECT_EMAIL.'" placeholder="'.($labels_position == 0 ? $myEmailLabel.$mandatoryMark : '').'" />';
		print '</div>'."\n";
	}

	// print repeat email
	if ($repeat_email_switch == 1) {
		if ($repeat_email_position == 0) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($repeat_email_position == 0) ? '<label class="form-label">'.$myRepeatEmailLabel.$mandatoryMark.'</label>' : '';
			}
			print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_repeat_email" value="'.$CORRECT_REPEAT_EMAIL.'" placeholder="'.($labels_position == 0 ? $myRepeatEmailLabel.$mandatoryMark : '').'" />';
			print '</div>'."\n";
		}
	}
	
	// print subject
	if ($subjectSwitch == 1) {
		if ($subject_position == 0) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($subject_position == 0) ? '<label class="form-label">'.$mySubjectLabel.$mandatoryMark.'</label>' : '';
			}
			print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_subject" value="'.$CORRECT_SUBJECT.'" placeholder="'.($labels_position == 0 ? $mySubjectLabel.$mandatoryMark : '').'" />';
			print '</div>'."\n";
		}
	}
	
	// print other fields
	if ($otherFields) {
		$otherFieldsLeft = preg_split('/\r\n|\r|\n/', $otherFields);
		foreach ($otherFieldsLeft as $field) {
			$fieldValues = explode(',', $field);
			if ($fieldValues[2] == 'left') {
				print '<div class="mb-3">';
				if ($labels_position == 1) {
					print '<label class="form-label">'.$fieldValues[0].($fieldValues[3] == 'yes' ? $mandatoryMark : '').'</label>';
				}
				print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_'.$fieldValues[1].'" value="'.$fieldTag.'" placeholder="'.($labels_position == 0 ? $fieldValues[0].($fieldValues[3] == 'yes' ? $mandatoryMark : '') : '').'" />';
				print '</div>'."\n";
			}
		}
	}
	
	// print anti-spam
	if ($enable_anti_spam) {
		if ($anti_spam_position == 0) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($anti_spam_position == 0) ? '<label class="form-label">'.$myAntiSpamQuestion.$mandatoryMark.'</label>' : '';
			}
			print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_anti_spam_answer" value="'.$CORRECT_ANTISPAM_ANSWER.'" placeholder="'.($labels_position == 0 ? $myAntiSpamQuestion.$mandatoryMark : '').'" />';
			print '</div>'."\n";
		}
	}
	
	// print captcha
	if ($enable_captcha && $captcha_position == 0) {
		$config = JFactory::getConfig();
		$captchaType = $config->get('captcha');
		$reCaptchaName = $captchaType;
		if ($captchaType != '0') {
			$captcha = JCaptcha::getInstance($captchaType);
		  	/*
		  	try {
				if (!$captcha->checkAnswer(JFactory::getApplication()->input->get('rp_recaptcha', null, 'string'))) {
					$myError = $wrongcaptcha;
				}
		  	}
		  	catch(RuntimeException $e) {
				$myError = $wrongcaptcha;
		  	}
		  	*/
		}
		$captcha_field = ($captchaType != '0') ? JCaptcha::getInstance($captchaType)->display('rp_recaptcha', 'rp_recaptcha', 'g-recaptcha') : '';
		print $captcha_field;
	}
	
	// deprecated since 1.6.1
	/*
		$config = JFactory::getConfig();
		$captchaType = $config->get('captcha');
		$reCaptchaName = $captchaType;
		JPluginHelper::importPlugin('captcha', $reCaptchaName);
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('onInit', 'dynamic_captcha');
		$captcha = $dispatcher->trigger('onDisplay', array('', 'dynamic_captcha', 'class="captcha"'));
		print $captcha[0];
	*/

	// print message
	if ($messageSwitch) {
		if ($message_position == 0) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($message_position == 0) ? '<label class="form-label">'.$myMessageLabel.$mandatoryMark.'</label>' : '';
			}
			print '<textarea class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" name="digi_contact_message" style="height: '.$message_height.'" placeholder="'.($labels_position == 0 ? $myMessageLabel.$mandatoryMark : '').'">'.$CORRECT_MESSAGE.'</textarea>';
			print '</div>'."\n";
		}
	}
	
	// print privacy
	if ($enable_privacy) {
		print ($privacy_position == 0) ? '<div class="mb-3"><label class="checkbox"><input class="form-check-input" type="checkbox" name="digi_contact_privacy_check" /> '.($privacyLink ? '<a href="'.$privacyLink.'" title="'.$privacyLabel.'" target="_blank">'.$privacyLabel.'</a>' : $privacyLabel).$mandatoryMark.'</label></div>'."\n" : '';
	}
	
	// print send
	print '<input type="hidden" name="action" value="digi_contact_'.$module->id.'_action">';
	print ($send_position == 0) ? '<button value="digi_contact_'.$module->id.'_action" type="submit" class="'.$sendClass.' '.($fields_width == 1 ? ' btn-block' : '').'">' . $buttonText . '</button>' . "\n" : '';

print ($print_left == 1) ? '</div>' : '';

print ($print_right == 1) ? ($print_left == 1) ? '<div class="'.$colClass.'6">' : '<div>' : '';

	// print name
	if ($nameSwitch == 1) {
		if ($name_position == 1) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($name_position == 1) ? '<label class="form-label">'.$myNameLabel.$mandatoryMark.'</label>' : '';
			}
			print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_name" value="'.$CORRECT_NAME.'" placeholder="'.($labels_position == 0 ? $myNameLabel.$mandatoryMark : '').'" />';
			print '</div>'."\n";
		}
	}
	
	// print email
	if ($email_position == 1) {
		print '<div class="mb-3">';
		if ($labels_position == 1) {
			print ($email_position == 1) ? '<label class="form-label">'.$myEmailLabel.$mandatoryMark.'</label>' : '';
		}
		print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_email" value="'.$CORRECT_EMAIL.'" placeholder="'.($labels_position == 0 ? $myEmailLabel.$mandatoryMark : '').'" />';
		print '</div>'."\n";
	}

	// print repeat email
	if ($repeat_email_switch == 1) {
		if ($repeat_email_position == 1) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($repeat_email_position == 1) ? '<label class="form-label">'.$myRepeatEmailLabel.$mandatoryMark.'</label>' : '';
			}
			print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_repeat_email" value="'.$CORRECT_REPEAT_EMAIL.'" placeholder="'.($labels_position == 0 ? $myRepeatEmailLabel.$mandatoryMark : '').'" />';
			print '</div>'."\n";
		}
	}
	
	// print subject
	if ($subjectSwitch == 1) {
		if ($subject_position == 1) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($subject_position == 1) ? '<label class="form-label">'.$mySubjectLabel.$mandatoryMark.'</label>' : '';
			}
			print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_subject" value="'.$CORRECT_SUBJECT.'" placeholder="'.($labels_position == 0 ? $mySubjectLabel.$mandatoryMark : '').'" />';
			print '</div>'."\n";
		}
	}
	
	// print other fields
	if ($otherFields) {
		$otherFieldsRight = preg_split('/\r\n|\r|\n/', $otherFields);
		foreach ($otherFieldsRight as $field) {
			$fieldValues = explode(',', $field);
			if ($fieldValues[2] == 'right') {
				print '<div class="mb-3">';
				if ($labels_position == 1) {
					print '<label class="form-label">'.$fieldValues[0].($fieldValues[3] == 'yes' ? $mandatoryMark : '').'</label>';
				}
				print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_'.$fieldValues[1].'" value="'.$fieldTag.'" placeholder="'.($labels_position == 0 ? $fieldValues[0].($fieldValues[3] == 'yes' ? $mandatoryMark : '') : '').'" />';
				print '</div>'."\n";
			}
		}
	}
	
	// print anti-spam
	if ($enable_anti_spam) {
		if ($anti_spam_position == 1) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($anti_spam_position == 1) ? '<label class="form-label">'.$myAntiSpamQuestion.$mandatoryMark.'</label>' : '';
			}
			print '<input class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" type="text" name="digi_contact_anti_spam_answer" value="'.$CORRECT_ANTISPAM_ANSWER.'" placeholder="'.($labels_position == 0 ? $myAntiSpamQuestion.$mandatoryMark : '').'" />';
			print '</div>'."\n";
		}
	}
	
	// print captcha
	if ($enable_captcha && $captcha_position == 1) {
		$config = JFactory::getConfig();
		$captchaType = $config->get('captcha');
		$reCaptchaName = $captchaType;
		if ($captchaType != '0') {
			$captcha = JCaptcha::getInstance($captchaType);
		  	/*
		  	try {
				if (!$captcha->checkAnswer(JFactory::getApplication()->input->get('rp_recaptcha', null, 'string'))) {
					$myError = $wrongcaptcha;
				}
		  	}
		  	catch(RuntimeException $e) {
				$myError = $wrongcaptcha;
		  	}
		  	*/
		}
		$captcha_field = ($captchaType != '0') ? JCaptcha::getInstance($captchaType)->display('rp_recaptcha', 'rp_recaptcha', 'g-recaptcha') : '';
		print $captcha_field;
	}
	
	// deprecated since 1.6.1
	/*
		$config = JFactory::getConfig();
		$captchaType = $config->get('captcha');
		$reCaptchaName = $captchaType;
		JPluginHelper::importPlugin('captcha', $reCaptchaName);
		$dispatcher = JDispatcher::getInstance();
		$dispatcher->trigger('onInit', 'dynamic_captcha');
		$captcha = $dispatcher->trigger('onDisplay', array('', 'dynamic_captcha', 'class="captcha"'));
		print $captcha[0];
	*/

	// print message
	if ($messageSwitch) {
		if ($message_position == 1) {
			print '<div class="mb-3">';
			if ($labels_position == 1) {
				print ($message_position == 1) ? '<label class="form-label">'.$myMessageLabel.$mandatoryMark.'</label>' : '';
			}
			print '<textarea class="form-control'.($fields_width == 1 ? ' input-block-level' : '').'" name="digi_contact_message" style="height: '.$message_height.'" placeholder="'.($labels_position == 0 ? $myMessageLabel.$mandatoryMark : '').'">'.$CORRECT_MESSAGE.'</textarea>';
			print '</div>'."\n";
		}
	}
	
	// print privacy
	if ($enable_privacy) {
		print ($privacy_position == 1) ? '<div class="mb-3"><label class="checkbox"><input class="form-check-input" type="checkbox" name="digi_contact_privacy_check" /> '.($privacyLink ? '<a href="'.$privacyLink.'" title="'.$privacyLabel.'" target="_blank">'.$privacyLabel.'</a>' : $privacyLabel).$mandatoryMark.'</label></div>'."\n" : '';
	}
	
	// print send
	print '<input type="hidden" name="action" value="digi_contact_'.$module->id.'_action">';
	print ($send_position == 1) ? '<button value="digi_contact_'.$module->id.'_action" type="submit" class="'.$sendClass.' '.($fields_width == 1 ? ' btn-block' : '').'">' . $buttonText . '</button>' . "\n" : '';

print ($print_right == 1) ? '</div>' : '';

//end row-fluid
print '</div>';

if ($postTextHR && !empty($postText)) {
	print '<hr />';
}

if ($postText) {
	print '<p id="digi_contact_post_text" class="lead">'.$postText.'</p>' . "\n";
}

print '</form>';

print '</div>' . "\n";

return true;

?>