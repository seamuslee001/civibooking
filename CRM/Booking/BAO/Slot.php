<?php

class CRM_Booking_BAO_Slot extends CRM_Booking_DAO_Slot {


    /**
   * takes an associative array and creates a slot object
   *
   * the function extract all the params it needs to initialize the create a
   * slot object. the params array could contain additional unused name/value
   * pairs
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   * @param array $ids    the array that holds all the db ids
   *
   * @return object CRM_Booking_BAO_Slot object
   * @access public
   * @static
   */
  static function create(&$params) {
    $slotDAO = new CRM_Booking_DAO_Slot();
    $slotDAO->copyValues($params);
    return $slotDAO->save();
  }


    /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference ) an assoc array of name/value pairs
   * @param array $defaults (reference ) an assoc array to hold the flattened values
   *
     * @return object CRM_Booking_DAO_Slot object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $dao = new CRM_Booking_DAO_Slot();
    $dao->copyValues($params);
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $defaults);
      return $dao;
    }
    return NULL;
  }

  /**
   * Function to delete Slot
   *
   * @param  int  $id     Id of the Slot to be deleted.
   *
   * @return boolean
   *
   * @access public
   * @static
   */
  static function del($id) {
    //make sure sub slots get deleted as well
    $subSlots = CRM_Booking_BAO_SubSlot::getSubSlotSlot($id);
    foreach ($subSlots as $subSlotId => $subSlots) {
      CRM_Booking_BAO_SubSlot::del($subSlotId);
    }
    $dao = new CRM_Booking_DAO_Slot();
    $dao->id = $id;
    $dao->is_deleted = 1;
    return $dao->save();
  }


  /**
   * Function to delete Slot
   *
   * @param  int  $id     Id of the Slot to be deleted.
   *
   * @return boolean
   *
   * @access public
   * @static
   */
  static function cancel($id) {
    $dao = new CRM_Booking_DAO_Slot();
    $dao->id = $id;
    $dao->is_cancelled = 1;
    return $dao->save();
  }

  /**
  * Function to validate if slot is can be created
  * @param array $params array of slot that need to created
  *
  * @return boolean, message, slot detail
  *
  * @access public
  * @static
  */
  static function validateSlot($params){
    $qParams = array(
      1 => array($params['start'], 'String'),
      2 => array($params['end'], 'String'),
      3 => array($params['resource_id'], 'Integer')
    );
    $query = "
      SELECT civicrm_booking_slot.id
      FROM civicrm_booking_slot
      WHERE 1
      AND civicrm_booking_slot.is_deleted = 0
      AND civicrm_booking_slot.resource_id = %3
      AND  (%1 BETWEEN civicrm_booking_slot.start AND civicrm_booking_slot.end
            OR
           %2 BETWEEN civicrm_booking_slot.start AND civicrm_booking_slot.end)";

    if(isset($params['id'])){
      $qParams[4] = array($params['id'], 'Integer');
      $query .= "\nAND civicrm_booking_slot.id != %4";
    }
    require_once('CRM/Core/DAO.php');
    $dao = CRM_Core_DAO::executeQuery( $query , $qParams );
    while ($dao->fetch()) {
      return FALSE;

    }
    return TRUE;
  }

  /**
   * Function to compare if an input field is existing in array of slots
   *
   *
   * @param array $fields input parameters to find slot
   * @param array $array array of slots
   *
   * @return boolean, id of matching slot
   *
   * @access public
   * @static
   */
  static function findExistingSlot($fields, $slots){
    $keysToUnset = array('booking_id', 'id', 'quantity', 'note');
    CRM_Booking_Utils_Array::unsetArray($fields, $keysToUnset);
    foreach ($slots as $key => $value) {
      $id = $value['id'];
      CRM_Booking_Utils_Array::unsetArray($value, $keysToUnset);
      $value['start'] = CRM_Utils_Date::processDate($value['start']);
      $value['end'] =  CRM_Utils_Date::processDate($value['end']);
      if($fields == $value){
        return array(TRUE, $id);
      }
    }
    return array(FALSE, NULL);
  }

  /**
   * Given the list of params in the params array, fetch the object
   * and store the values in the values array
   *
   * @param array $params input parameters to find object
   * @param array $values output values of the object
   *
   * @return CRM_Event_BAO_ฺSlot|null the found object or null
   * @access public
   * @static
   */
  static function getValues(&$params, &$values, &$ids) {
    if (empty($params)) {
      return NULL;
    }
    $slot = new CRM_Booking_BAO_Slot();
    $slot->copyValues($params);
    $slot->find();
    $slots = array();
    while ($slot->fetch()) {
      $ids['slot'] = $slot->id;
      CRM_Core_DAO::storeValues($slot, $values[$slot->id]);
      $slots[$slot->id] = $slot;
    }
    return $slots;
  }

  static function getBookingSlot($bookingID){
    $params = array(1 => array( $bookingID, 'Integer'));

    $query = "
      SELECT civicrm_booking_slot.id,
             civicrm_booking_slot.booking_id,
             civicrm_booking_slot.resource_id,
             civicrm_booking_slot.config_id,
             civicrm_booking_slot.quantity,
             civicrm_booking_slot.start,
             civicrm_booking_slot.end,
             civicrm_booking_slot.note
      FROM civicrm_booking_slot
      WHERE 1
      AND civicrm_booking_slot.booking_id = %1
      AND civicrm_booking_slot.is_deleted = 0";

    $slots = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $slots[$dao->id] = array(
        'id' => $dao->id,
        'booking_id' => $dao->booking_id,
        'resource_id' => $dao->resource_id,
        'config_id' => $dao->config_id,
        'quantity' => $dao->quantity,
        'start' => $dao->start,
        'end' => $dao->end,
        'note' => $dao->note,
      );
    }
    return $slots;
  }


  static function getSlotBetweenDate($from, $to){

    $params = array(1 => array( $from, 'String'),
                    2 => array( $to, 'String'));

    $query = "
      SELECT civicrm_booking_slot.id,
             civicrm_booking_slot.booking_id,
             civicrm_booking_slot.resource_id,
             civicrm_booking_slot.config_id,
             civicrm_booking_slot.start,
             civicrm_booking_slot.end,
             civicrm_booking_slot.note
      FROM civicrm_booking_slot
      WHERE 1
      AND
      (%1 BETWEEN DATE(civicrm_booking_slot.start) AND DATE(civicrm_booking_slot.end)
        OR
      %2 BETWEEN DATE(civicrm_booking_slot.start) AND DATE(civicrm_booking_slot.end))
      AND civicrm_booking_slot.is_deleted = 0 AND civicrm_booking_slot.is_cancelled = 0";

    $slots = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $slots[$dao->id] = array(
        'id' => $dao->id,
        'booking_id' => $dao->booking_id,
        'resource_id' => $dao->resource_id,
        'config_id' => $dao->config_id,
        'start' => $dao->start,
        'end' => $dao->end,
        'note' => $dao->note,
      );
    }

    return $slots;

  }


}
