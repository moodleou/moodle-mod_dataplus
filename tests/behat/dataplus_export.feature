@ou @ou_vle @mod @mod_dataplus @mod_dataplus_export
Feature: DataPlus database Export
  In order to allow students and teachers effective non-moodle database functionality
  As a student or a teacher
  I need to be able to export databases

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | teacher1 | Teacher   | 1        | teacher1@asd.com |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
      | student1 | C1     | student        |

    When I log in as "admin"

    And I expand "Site administration" node
    Then I set the following system permissions of "Teacher" role:
      | capability                   | permission |
      | mod/dataplus:downloadfull    |   Allow    |
    And I log out

    And I log in as "teacher1"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "DataPlus" to section "1" and I fill the form with:
      | Name | Behat DataPlus instance |
    And I follow "Behat DataPlus instance"
    And I follow "Import DataPlus Database"
    And I upload "mod/dataplus/tests/fixtures/dataplus2.zip" file to "Database" filemanager
    And I press "id_submitbutton"
    And I log out


  @javascript
  Scenario: Check database exports

    When I log in as "student1"
    And I am on site homepage
    Then I follow "Course 1"
    And I follow "Behat DataPlus instance"
    And I follow "Export"
    And following "Download SQLite database" should download between "94000" and "95000" bytes
    And following "Download in CSV format" should download between "93000" and "93500" bytes

    # Now check fully compatible download is not available to students

    And "Download fully compatible DataPlus database" "link" should not exist

    # Now check fully compatible download is available to teachers

    And I log out
    And I log in as "teacher1"
    And I am on site homepage
    And I follow "Course 1"
    And I follow "Behat DataPlus instance"
    And I follow "Export"
    Then following "Download fully compatible DataPlus database" should download between "94000" and "95000" bytes
