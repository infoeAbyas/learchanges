<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Generic moodleforms field.
 *
 * @package    core_form
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Session as Session,
    Behat\Mink\Element\NodeElement as NodeElement;

/**
 * Representation of a form field.
 *
 * Basically an interface with Mink session.
 *
 * @package    core_form
 * @category   test
 * @copyright  2012 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_form_field {

    /**
     * @var Session Behat session.
     */
    protected $session;

    /**
     * @var NodeElement The field DOM node to interact with.
     */
    protected $field;

    /**
     * General constructor with the node and the session to interact with.
     *
     * @param Session $session Reference to Mink session to traverse/modify the page DOM.
     * @param NodeElement $fieldnode The field DOM node
     * @return void
     */
    public function __construct(Session $session, NodeElement $fieldnode) {
        $this->session = $session;
        $this->field = $fieldnode;
    }

    /**
     * Sets the value to a field.
     *
     * @param string $value
     * @return void
     */
    public function set_value($value) {
        // We delegate to the best guess, if we arrived here
        // using the generic behat_form_field is because we are
        // dealing with a fgroup element.
        $instance = $this->guess_type();
        return $instance->set_value($value);
    }

    /**
     * Returns the current value of the select element.
     *
     * @return string
     */
    public function get_value() {
        // We delegate to the best guess, if we arrived here
        // using the generic behat_form_field is because we are
        // dealing with a fgroup element.
        $instance = $this->guess_type();
        return $instance->get_value();
    }

    /**
     * Generic match implementation
     *
     * Will work well with text-based fields, extension required
     * for most of the other cases.
     *
     * @param string $expectedvalue
     * @return bool The provided value matches the field value?
     */
    public function matches($expectedvalue) {
        // We delegate to the best guess, if we arrived here
        // using the generic behat_form_field is because we are
        // dealing with a fgroup element.
        $instance = $this->guess_type();
        return $instance->matches($expectedvalue);
    }

    /**
     * Guesses the element type we are dealing with in case is not a text-based element.
     *
     * This class is the generic field type, behat_field_manager::get_form_field()
     * should be able to find the appropiate class for the field type, but
     * in cases like moodle form group elements we can not find the type of
     * the field through the DOM so we also need to take care of the
     * different field types from here. If we need to deal with more complex
     * moodle form elements we will need to refactor this simple HTML elements
     * guess method.
     *
     * @return behat_form_field
     */
    private function guess_type() {
        global $CFG;

        // We default to the text-based field if nothing was detected.
        if (!$type = behat_field_manager::guess_field_type($this->field, $this->session)) {
            $type = 'text';
        }

        $classname = 'behat_form_' . $type;
        $classpath = $CFG->dirroot . '/lib/behat/form_field/' . $classname . '.php';
        require_once($classpath);
        return new $classname($this->session, $this->field);
    }

    /**
     * Returns whether the scenario is running in a browser that can run Javascript or not.
     *
     * @return bool
     */
    protected function running_javascript() {
        return get_class($this->session->getDriver()) !== 'Behat\Mink\Driver\GoutteDriver';
    }

    /**
     * Gets the field internal id used by selenium wire protocol.
     *
     * Only available when running_javascript().
     *
     * @throws coding_exception
     * @return int
     */
    protected function get_internal_field_id() {

        if (!$this->running_javascript()) {
            throw new coding_exception('You can only get an internal ID using the selenium driver.');
        }

        return $this->session->getDriver()->getWebDriverSession()->element('xpath', $this->field->getXPath())->getID();
    }

    /**
     * Checks if the provided text matches the field value.
     *
     * @param string $expectedvalue
     * @return bool
     */
    protected function text_matches($expectedvalue) {
        if (trim($expectedvalue) != trim($this->get_value())) {
            return false;
        }
        return true;
    }
}
