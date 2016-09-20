@ou @ou_vle @mod @mod_dataplus @mod_dataplus_student
Feature: Student use of a DataPlus database
  In order to allow students non-moodle database functionality
  As a student
  I need to be able to view, add, delete and search records

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email            |
      | student1 | Student   | 1        | student1@asd.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | student1 | C1     | student        |

    When I log in as "admin"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "DataPlus" to section "1" and I fill the form with:
      | Name | Behat DataPlus instance |
    And I follow "Behat DataPlus instance"
    And I follow "Import DataPlus Database"
    And I upload "mod/dataplus/tests/fixtures/dataplus1.zip" file to "Database" filemanager
    And I press "id_submitbutton"
    And I log out


  @javascript
  Scenario: Student use of the database

    Given I log in as "student1"
    And I am on site homepage
    And I follow "Course 1"
    When I follow "Behat DataPlus instance"

    # Check elements of database appear correctly

    Then "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='Textfield text']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='The lazy brown dog jumps over the big cow']" "xpath_element" should exist
    And I should see "01 September 2014"
    And I should see "01 October 2015 12:30"
    And "testfile.txt" "link" should exist
    And the "href" attribute of "Google site" "link" should contain "http://www.google.co.uk"
    And I should see "true"
    And I should see "two"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='number']/parent::*/following-sibling::td[text()='888']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alt'][@title='image alt']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='file']/parent::*/following-sibling::td/a[text()='testfile.txt']" "xpath_element" should exist

    # Add a record

    When I follow "Add record"
    And I set the following fields to these values:
      | textfield                | student added text    |
      | multipletextfield        | multiple text         |
      | id_datefield_day         | 1                     |
      | id_datefield_month       | June                  |
      | id_datefield_year        | 2014                  |
      | id_datetimefield_day     | 1                     |
      | id_datetimefield_month   | May                   |
      | id_datetimefield_year    | 2015                  |
      | id_datetimefield_hour    | 07                    |
      | id_datetimefield_minute  | 45                    |
      | number                   | 999                   |
      | image descriptor         | image alternative     |
      | url                      | www.bbc.co.uk         |
      | url descriptor           | BBc site              |
      | truefalse                | 2                     |
      | menu                     | three                 |
    And I upload "mod/dataplus/tests/fixtures/testimage.png" file to "image" filemanager
    And I press "Save"

    # Check records appear correctly

    When I click on "//div/ul/li/a[text()='Single record']" "xpath_element"

    # I should see the First record

    Then "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='Textfield text']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='The lazy brown dog jumps over the big cow']" "xpath_element" should exist
    And I should see "01 September 2014"
    And I should see "01 October 2015 12:30"
    And "testfile.txt" "link" should exist
    And the "href" attribute of "Google site" "link" should contain "http://www.google.co.uk"
    And I should see "true"
    And I should see "two"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='number']/parent::*/following-sibling::td[text()='888']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alt'][@title='image alt']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='file']/parent::*/following-sibling::td/a[text()='testfile.txt']" "xpath_element" should exist

    # I should not see any other records on this page

    And I should not see "01 June 2014"
    And I should not see "01 May 2015 07:45"
    And I should not see "999"
    And "Previous" "link" should not exist

    # Now look at the second record

    When I follow "Next"

    Then "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='student added text']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='multiple text']" "xpath_element" should exist
    And I should see "01 June 2014"
    And I should see "01 May 2015 07:45"
    And I should see "999"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alternative'][@title='image alternative']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.bbc.co.uk']" "xpath_element" should exist
    And I should see "true"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='menu']/parent::*/following-sibling::td[text()='three']" "xpath_element" should exist

    # Check first record is not visible here

    And I should not see "01 September 2014"
    And I should not see "01 October 2015 12:30"
    And I should not see "two"
    And "Previous" "link" should exist
    And "Next" "link" should not exist

    # Simple search for a record

    When I click on "//div/ul/li/a[text()='Search']" "xpath_element"

    And I set the following fields to these values:
      | textfield | student added text |
      | menu      | three              |
    And I click on "//div/input[@value='Search']" "xpath_element"

    Then "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='student added text']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='multiple text']" "xpath_element" should exist
    And I should see "01 June 2014"
    And I should see "01 May 2015 07:45"
    And I should see "999"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alternative'][@title='image alternative']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.bbc.co.uk']" "xpath_element" should exist
    And I should see "false"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='menu']/parent::*/following-sibling::td[text()='three']" "xpath_element" should exist
    And "Amend search" "link" should exist

    # Advanced search.

    When I follow "Add record"

    And I set the following fields to these values:
      | textfield                | student added text    |
      | multipletextfield        | multiple text         |
      | id_datefield_day         | 1                     |
      | id_datefield_month       | June                  |
      | id_datefield_year        | 2014                  |
      | id_datetimefield_day     | 1                     |
      | id_datetimefield_month   | May                   |
      | id_datetimefield_year    | 2015                  |
      | id_datetimefield_hour    | 07                    |
      | id_datetimefield_minute  | 45                    |
      | number                   | 999                   |
      | image descriptor         | image alternative     |
      | url                      | www.bbc.co.uk         |
      | url descriptor           | BBc site              |
      | truefalse                | 2                     |
      | menu                     | three                 |
    And I upload "mod/dataplus/tests/fixtures/testimage.png" file to "image" filemanager
    And I press "Save"

    And I set the following fields to these values:
      | textfield                | student added text    |
      | multipletextfield        | multiple text 2       |
      | id_datefield_day         | 1                     |
      | id_datefield_month       | June                  |
      | id_datefield_year        | 2014                  |
      | id_datetimefield_day     | 1                     |
      | id_datetimefield_month   | May                   |
      | id_datetimefield_year    | 2015                  |
      | id_datetimefield_hour    | 07                    |
      | id_datetimefield_minute  | 45                    |
      | number                   | 777                   |
      | image descriptor         | image alternative     |
      | url                      | www.bbc.co.uk         |
      | url descriptor           | BBC site              |
      | truefalse                | 2                     |
      | menu                     | two                   |
    And I upload "mod/dataplus/tests/fixtures/testimage.png" file to "image" filemanager
    And I press "Save"

    And I click on "//div/ul/li/a[text()='Search']" "xpath_element"
    And I follow "Advanced search"

    # Construct Advanced search

    And I set the following fields to these values:
      | textfield                | text                  |
      | multipletextfield        | t                     |
      | id_datefield_day         | 20                    |
      | id_datefield_month       | February              |
      | id_datefield_year        | 2010                  |
      | id_datetimefield_day     | 2                     |
      | id_datetimefield_month   | April                 |
      | id_datetimefield_year    | 2020                  |
      | id_datetimefield_hour    | 14                    |
      | id_datetimefield_minute  | 00                    |
      | number                   | 200                   |
      | url                      | www                   |
      | menu                     | two                   |
      | Sort level 1             | number                |
    And I click on "contains" "radio" in the "div#fgroup_id_textfield_specificity_specificity" "css_element"
    And I click on "contains" "radio" in the "div#fgroup_id_multipletextfield_specificity_specificity" "css_element"
    And I click on "since" "radio" in the "div#fgroup_id_datefield_arrow" "css_element"
    And I click on "before" "radio" in the "div#fgroup_id_datetimefield_arrow" "css_element"
    And I click on "greater than" "radio" in the "div#fgroup_id_number_arrow" "css_element"
    And I click on "contains" "radio" in the "div#fgroup_id_url_specificity_specificity" "css_element"
    And I click on "true" "radio" in the "div#fgroup_id_truefalse" "css_element"
    And I click on "ascending" "radio" in the "div#fgroup_id_sort_options0" "css_element"

    And I click on "//div/input[@value='Search']" "xpath_element"

    # View search result

    And "Amend search" "link" should exist

    # 1st record

    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='student added text']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='multiple text 2']" "xpath_element" should exist
    And I should see "01 June 2014"
    And I should see "01 May 2015 07:45"
    And I should see "777"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alternative'][@title='image alternative']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.bbc.co.uk']" "xpath_element" should exist
    And I should see "true"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='menu']/parent::*/following-sibling::td[text()='two']" "xpath_element" should exist

    # 2nd record

    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='Textfield text']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='The lazy brown dog jumps over the big cow']" "xpath_element" should exist
    And I should see "01 September 2014"
    And I should see "01 October 2015 12:30"
    And I should see "888"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alt'][@title='image alt']" "xpath_element" should exist
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.google.co.uk']" "xpath_element" should exist
    And I should see "true"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='menu']/parent::*/following-sibling::td[text()='two']" "xpath_element" should exist

    # Check ordering

    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='multiple text 2']" "xpath_element" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='student added text']" "xpath_element"
    And "01 June 2014" "text" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='multiple text 2']" "xpath_element"
    And "01 May 2015 07:45" "text" should appear after "01 June 2014" "text"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alternative'][@title='image alternative']" "xpath_element" should appear after "777" "text"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.bbc.co.uk']" "xpath_element" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alternative'][@title='image alternative']" "xpath_element"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='Textfield text']" "xpath_element" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.bbc.co.uk']" "xpath_element"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='The lazy brown dog jumps over the big cow']" "xpath_element" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='Textfield text']" "xpath_element"
    And "01 September 2014" "text" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='The lazy brown dog jumps over the big cow']" "xpath_element"
    And "01 October 2015 12:30" "text" should appear after "01 September 2014" "text"
    And "888" "text" should appear after "01 October 2015 12:30" "text"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alt'][@title='image alt']" "xpath_element" should appear after "888" "text"
    And "testfile.txt" "link" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alt'][@title='image alt']" "xpath_element"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.google.co.uk']" "xpath_element" should appear after "testfile.txt" "link"

    # Confirm the remaining record is not visible

    And I should not see "999"
    And I should not see "three"

    # Confirm only 2 and not 3 records are not displayed

    And I should see "records 1 - 2 of 2"
    And I should not see "records 1 - 3 of 3"

    # Now amend the sort order in the advanced search and check

    And I follow "Amend search"
    And I set the following fields to these values:
      | Sort level 1 |  datefield  |
    And I click on "descending" "radio" in the "div#fgroup_id_sort_options0" "css_element"
    And I click on "//div/input[@value='Search']" "xpath_element"

    # Now check the amended sort order view

    And "Amend search" "link" should exist

    And "student added text" "text" should appear after "Textfield text" "text"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='The lazy brown dog jumps over the big cow']" "xpath_element" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='Textfield text']" "xpath_element"
    And "01 September 2014" "text" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='The lazy brown dog jumps over the big cow']" "xpath_element"
    And "01 October 2015 12:30" "text" should appear after "01 September 2014" "text"
    And "888" "text" should appear after "01 October 2015 12:30" "text"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alt'][@title='image alt']" "xpath_element" should appear after "888" "text"
    And "testfile.txt" "link" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alt'][@title='image alt']" "xpath_element"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.google.co.uk']" "xpath_element" should appear after "testfile.txt" "link"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='student added text']" "xpath_element" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.google.co.uk']" "xpath_element"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='multiple text 2']" "xpath_element" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='textfield']/parent::*/following-sibling::td[text()='student added text']" "xpath_element"
    And "01 June 2014" "text" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='multipletextfield']/parent::*/following-sibling::td/div[text()='multiple text 2']" "xpath_element"
    And "01 May 2015 07:45" "text" should appear after "01 June 2014" "text"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alternative'][@title='image alternative']" "xpath_element" should appear after "777" "text"
    And "//div[@class='dataplus_record']/table//tr/td/strong[text()='url']/parent::*/following-sibling::td/a[@href='http://www.bbc.co.uk']" "xpath_element" should appear after "//div[@class='dataplus_record']/table//tr/td/strong[text()='image']/parent::*/following-sibling::td/img[@alt='image alternative'][@title='image alternative']" "xpath_element"

    # Confirm elements of the remaining record are not visible

    And I should not see "999"
    And I should not see "three"

    # Confirm only 2 and not 3 records are not displayed

    And I should see "records 1 - 2 of 2"
    And I should not see "records 1 - 3 of 3"

