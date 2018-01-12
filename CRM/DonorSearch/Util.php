<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_DonorSearch_Util
 */
class CRM_DonorSearch_Util {

  /**
   * Update the current Donor Search data of a contact
   */
  public static function updateRecord() {
    $dao = new CRM_DonorSearch_DAO_SavedSearch();
    $self = NULL;
    $dao->id = CRM_Utils_Request::retrieve('id', 'Positive', $self, TRUE);
    $dao->find(TRUE);
    $previousDSparams = unserialize($dao->search_criteria);

    // If Donor Search API key is missing
    if (empty($previousDSparams['key'])) {
      $apiKey = Civi::settings()->get('ds_api_key');
      if (empty($apiKey)) {
        CRM_Core_Error::fatal(ts("Donor Search API key missing. Navigate to Administer >> System Settings >> Register Donor Search API Key to register API key"));
      }
      $previousDSparams['key'] = $apiKey;
    }

    // Fetch Donor Search data via GET api
    $apiRequest = CRM_DonorSearch_API::singleton($previousDSparams);
    list($isError, $response) = $apiRequest->get();

    // If there is no record found for given Search ID then register a new search
    // using search parameters used earlier via SEND api. This will return the
    // corrosponding donor search data which is later stored against logged in contact ID
    if ($isError && (trim($response) == 'No records found')) {
      if (!empty($previousDSparams)) {
        list($isError, $response) = $apiRequest->send();
      }
    }

    // update DS data recieved from GET or SEND api above, against contact ID (as search ID)
    if (!$isError) {
      self::processDSData($response, $previousDSparams['id']);
    }

    // show status and redirect to 'Donor Integrated Search' page
    CRM_Core_Session::setStatus(ts("DS Record updated for Contact ID - " . $previousDSparams['id']), ts('Success'), 'success');
    CRM_Utils_System::redirect(self::getDonorSearchDetailsLink($dao->contact_id));
  }

  /**
   * Process Donor Search data in XML format, recieved from SEND or GET api
   *
   * @param string $response
   *   donor search data in xml format
   * @param int $contactID
   *   contact ID as search ID
   *
   * @return array
   */
  public static function processDSData($response, $contactID) {
    // encode the raw DS data to html entites which is basically in xml format
    $response = html_entity_decode(str_replace('<pre>', '', $response));
    list($xml, $error) = CRM_Utils_XML::parseString($response);
    // if there is any error while parsing into xml data, abort the process and throw desired error
    if ($error) {
      CRM_Core_Error::fatal(ts($error));
    }

    // useful to format api param by placing value against
    // corresponding custom field that represent a DS attribute
    $xmlToFieldMap = CRM_DonorSearch_FieldInfo::getXMLToCustomFieldNameMap();
    // convert the xml obj to array
    $xmlData = CRM_Utils_XML::xmlObjToArray($xml);

    $param = array('id' => $contactID);
    // set value against its desired custom field that represent a DS attribute
    foreach ($xmlData as $xmlName => $value) {
      // as per the documentation there are few attributes which are optional and can be ignored
      if (!array_key_exists($xmlName, $xmlToFieldMap)) {
        continue;
      }
      $param[$xmlToFieldMap[$xmlName]] = $value;
    }

    // update the contact (id - $contactID) with donor search data
    civicrm_api3('Contact', 'create', $param);

    return $xmlData;
  }

  /**
   * View the desired Donor Search profile of a contact
   */
  public static function viewProfile() {
    $self = NULL;
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $self, TRUE);
    $profileLink = civicrm_api3('Contact', 'getvalue', array(
      'id' => $cid,
      'return' => CRM_DonorSearch_FieldInfo::getXMLToCustomFieldNameMap('profile_link'),
    ));

    if ($profileLink) {
      CRM_Utils_System::redirect($profileLink);
    }
    else {
      CRM_Core_Error::fatal(ts('There is no Donor Search profile'));
    }
  }

  /**
   * Get donor search custom group view link
   */
  public static function getDonorSearchDetailsLink($contactID) {
    $customGroupID = civicrm_api3('customGroup', 'getvalue', array(
      'name' => 'DS_details',
      'return' => 'id',
    ));
    return CRM_Utils_System::url('civicrm/contact/view', sprintf('reset=1&gid=%d&cid=%d&selectedChild=custom_%d', $customGroupID, $contactID, $customGroupID));
  }

}
