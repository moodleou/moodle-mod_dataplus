@ou @ou_vle @mod @mod_dataplus @mod_dataplus_basic
Feature: Basic DataPlus database creation, import and deletion
  In order to allow students and teachers database functionality
  As a teacher
  I need to be able to create, configure, import and clear data from DataPlus on a course

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: Manage DataPlus instanced on a course

    Given I log in as "teacher1"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "DataPlus" to section "1" and I fill the form with:
      | Name | Behat DataPlus instance |
    When I follow "Behat DataPlus instance"
    Then "Setup database" "link" should exist
    And "Import DataPlus Database" "link" should exist

    When I follow "Setup database"
    And I set the following fields to these values:
      | id_fieldname0    | textfield             |
      | id_fieldname1    | multipletextfield     |
      | id_fieldname2    | datefield             |
      | id_fieldname3    | datetimefield         |
      | id_fieldtype0    | Text (one line)       |
      | id_fieldtype1    | Text (multiple lines) |
      | id_fieldtype2    | Date                  |
      | id_fieldtype3    | Date / time           |
    And I press "Save changes"
    And I set the following fields to these values:
      | id_fieldname0    | number                |
      | id_fieldtype0    | Number                |
    And I press "Save changes"
    And I set the following fields to these values:
      | id_fieldname0    | image                 |
      | id_fieldtype0    | Image                 |
    And I press "Save changes"
    And I set the following fields to these values:
      | id_fieldname0    | file                  |
       | id_fieldtype0   | File                  |
    And I press "Save changes"
    And I set the following fields to these values:
      | id_fieldname0    | url                   |
      | id_fieldtype0    | URL                   |
    And I press "Save changes"
    And I set the following fields to these values:
      | id_fieldname0    | truefalse             |
      | id_fieldtype0    | True / false          |
    And I press "Save changes"
    And I set the following fields to these values:
      | id_fieldname0    | menu                  |
      | id_fieldtype0    | Menu                  |
    And the dataplus textarea for "Options - one per line" is set with the following lines:
      | one   |
      | two   |
      | three |
    And I press "Save changes"

    # Now check view appears correctly

    Then I should see "textfield"
    And I should see "Text (one line)"
    And I should see "multipletextfield"
    And I should see "Text (multiple lines)"
    And I should see "datefield"
    And I should see "Date"
    And I should see "datetimefield"
    And I should see "Date / time"
    And I should see "number"
    And I should see "Number"
    And I should see "image"
    And I should see "Image"
    And I should see "file"
    And I should see "File"
    And I should see "url"
    And I should see "URL"
    And I should see "truefalse"
    And I should see "True / false"
    And I should see "menu"
    And I should see "Menu (single selection)"
    And the "alt" attribute of "//tr/td[text()='textfield']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='textfield']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And the "alt" attribute of "//tr/td[text()='multipletextfield']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='multipletextfield']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And the "alt" attribute of "//tr/td[text()='datefield']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='datefield']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And the "alt" attribute of "//tr/td[text()='datetimefield']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='datetimefield']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And the "alt" attribute of "//tr/td[text()='number']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='number']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And the "alt" attribute of "//tr/td[text()='image']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='image']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And the "alt" attribute of "//tr/td[text()='file']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='file']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And the "alt" attribute of "//tr/td[text()='url']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='url']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And the "alt" attribute of "//tr/td[text()='truefalse']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='truefalse']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And the "alt" attribute of "//tr/td[text()='menu']/following-sibling::td/a[@class='action-icon'][1]/img" "xpath_element" should contain "Edit"
    And the "alt" attribute of "//tr/td[text()='menu']/following-sibling::td/a[@class='action-icon'][2]/img" "xpath_element" should contain "Delete"
    And "Add to your database" "link" should exist

    # Test import of a database.

    And I am on homepage
    And I follow "Course 1"
    And I add a "DataPlus" to section "2" and I fill the form with:
      | Name | Behat DataPlus instance 2 |
    And I follow "Behat DataPlus instance 2"
    And I follow "Import DataPlus Database"
    When I upload "mod/dataplus/tests/fixtures/dataplus2.zip" file to "Database" filemanager
    And I press "id_submitbutton"
    Then I should see "Import complete"
    And I follow "View database"

    # Check elements of database appear correctly

    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='Textfield text']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='The lazy brown dog jumps over the big cow']" "xpath_element" should exist
    And I should see "01 September 2014"
    And I should see "01 October 2015 12:30"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='number']/parent::*/following-sibling::td[text()='888']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alt'][@title='image alt']" "xpath_element" should exist
    And "testfile.txt" "link" should exist
    And the "href" attribute of "Google site" "link" should contain "http://www.google.co.uk"
    And I should see "true"
    And I should see "two"

    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='student added text']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='multiple text']" "xpath_element" should exist
    And I should see "01 June 2014"
    And I should see "01 May 2015 14:45"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='number']/parent::*/following-sibling::td[text()='999']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alternative'][@title='image alternative']" "xpath_element" should exist
    And the "href" attribute of "BBc" "link" should contain "http://www.bbc.co.uk"
    And I should see "three"

    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='multiple text 2']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='number']/parent::*/following-sibling::td[text()='777']" "xpath_element" should exist

    # Test deletion of data.

    When I follow "Manage"
    And I follow "Clear data"
    And I press "Confirm clear data"
    Then I should see "All data has been cleared from the database"
    And I click on "//div/ul/li/a[@title='View'][text()='View']" "xpath_element"
    And I should see "This database is currently empty"
    And "//div[@class='dataplus_record']" "xpath_element" should not exist
