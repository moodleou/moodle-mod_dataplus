@ou @ou_vle @mod @mod_dataplus @mod_dataplus_template
Feature: Basic DataPlus template modification
  In order to allow students database functionality
  As a teacher or media team
  I need to be able to create and configure DataPlus templates

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

    And I log in as "admin"
    And I expand "Site administration" node
    And I set the following system permissions of "Teacher" role:
      | capability                   | permission |
      | mod/dataplus:downloadfull    |   Allow    |
    And I log out

    And I log in as "teacher1"
    And I am on site homepage
    And I follow "Course 1"
    And I turn editing mode on
    And I add a "DataPlus" to section "1" and I fill the form with:
      | Name | Behat DataPlus instance |
    When I follow "Behat DataPlus instance"
    Then "Setup database" "link" should exist
    And "Import DataPlus Database" "link" should exist


  @javascript
  Scenario: Use templates

    Given I follow "Setup database"
    When I set the following fields to these values:
      | id_fieldname0    | textfield             |
      | id_fieldname1    | multipletextfield     |
      | id_fieldname2    | datefield             |
      | id_fieldname3    | datetimefield         |
      | id_fieldtype0    | Text (one line)       |
      | id_fieldtype1    | Text (multiple lines) |
      | id_fieldtype2    | Date                  |
      | id_fieldtype3    | Date / time           |
    Then I press "Save changes"
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

    And I follow "Add record"
    And I set the following fields to these values:
      | textfield                | Textfield text                            |
      | multipletextfield        | The lazy brown dog jumps over the big cow |
      | id_datefield_day         | 1                                         |
      | id_datefield_month       | September                                 |
      | id_datefield_year        | 2014                                      |
      | id_datetimefield_day     | 1                                         |
      | id_datetimefield_month   | October                                   |
      | id_datetimefield_year    | 2015                                      |
      | id_datetimefield_hour    | 05                                        |
      | id_datetimefield_minute  | 30                                        |
      | number                   | 888                                       |
      | image descriptor         | image alt                                 |
      | url                      | www.google.co.uk                          |
      | url descriptor           | Google site                               |
      | truefalse                | 1                                         |
      | menu                     | two                                       |
    And I upload "mod/dataplus/tests/fixtures/unit1_front.jpg" file to "image" filemanager
    And I press "Save"
    And I follow "Single record"
    And I follow "edit"
    And I upload "mod/dataplus/tests/fixtures/testfile.txt" file to "file" filemanager
    And I press "Save"


    # Templates.

    And I follow "Templates"

    And "View template" "link" should exist
    And "Single record template" "link" should exist
    And "Add/edit record template" "link" should exist
    And "Help with template hooks" "link" should exist

    And "Show CSS" "button" should exist
    And "Show Javascript" "button" should exist
    And "Reset template" "button" should exist
    And "Enable editor" "button" should exist

    And I should see "Add record functions"
    And I should see "##Record count##"
    And I should see "##Record navigation##"

    And I should see "Add fields to your record"
    And I should see "[[textfield]]"
    And I should see "[[multipletextfield]]"
    And I should see "[[datefield]]"
    And I should see "[[datetimefield]]"
    And I should see "[[number]]"
    And I should see "[[image]]"
    And I should see "[[file]]"
    And I should see "[[url]]"
    And I should see "[[truefalse]]"
    And I should see "[[menu]]"

    And I should see "Add actions to your record"
    And I should see "**edit**"
    And I should see "**delete**"
    And I should see "**more**"
    And I should see "**rate**"

    And I should see "Add additional information to your record"
    And I should see "##id##"
    And I should see "##Creator##"
    And I should see "##Creator id##"
    And I should see "##Created##"
    And I should see "##Updater##"
    And I should see "##Updater id##"
    And I should see "##Updated##"
    And I should see "##Group id##"
    And I should see "##Record number##"

    And I should see ">##Record navigation##</div>" in the "textarea#id_header" "css_element"
    And I should see "><tr><td><strong>textfield</strong></td><td>[[textfield]]</td></tr><tr><td><strong>multipletextfield</strong></td><td>[[multipletextfield]]</td></tr><tr><td><strong>datefield</strong></td><td>[[datefield]]</td></tr><tr><td><strong>datetimefield</strong></td><td>[[datetimefield]]</td></tr><tr><td><strong>number</strong></td><td>[[number]]</td></tr><tr><td><strong>image</strong></td><td>[[image]]</td></tr><tr><td><strong>file</strong></td><td>[[file]]</td></tr><tr><td><strong>url</strong></td><td>[[url]]</td></tr><tr><td><strong>truefalse</strong></td><td>[[truefalse]]</td></tr><tr><td><strong>menu</strong></td><td>[[menu]]</td></tr><tr><td colspan=" in the "textarea#id_record" "css_element"
    And I should see ">##Record count##</div>" in the "textarea#id_footer" "css_element"
    And I should see ">##Record navigation##</div>" in the "textarea#id_footer" "css_element"

    And I should see "Sort order"
    And the field "id_sortorder1" matches value "N\A"
    And "//span/input[@name='sortoption1']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption1']/following-sibling::label[text()='descending']" "xpath_element" should exist
    And the field "id_sortorder2" matches value "N\A"
    And "//span/input[@name='sortoption2']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption2']/following-sibling::label[text()='descending']" "xpath_element" should exist
    And the field "id_sortorder3" matches value "N\A"
    And "//span/input[@name='sortoption3']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption3']/following-sibling::label[text()='descending']" "xpath_element" should exist
    And the field "id_sortorder4" matches value "N\A"
    And "//span/input[@name='sortoption4']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption4']/following-sibling::label[text()='descending']" "xpath_element" should exist
    And the field "id_sortorder5" matches value "N\A"
    And "//span/input[@name='sortoption5']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption5']/following-sibling::label[text()='descending']" "xpath_element" should exist

    And "Save changes" "button" should exist


    # Single record template.

    And I follow "Single record template"

    And "View template" "link" should exist
    And "Single record template" "link" should exist
    And "Add/edit record template" "link" should exist
    And "Help with template hooks" "link" should exist

    And "Show CSS" "button" should exist
    And "Show Javascript" "button" should exist
    And "Reset template" "button" should exist
    And "Enable editor" "button" should exist

    And I should see "Add record functions"
    And I should see "##Record count##"
    And I should see "##Record navigation##"

    And I should see "Add fields to your record"
    And I should see "[[textfield]]"
    And I should see "[[multipletextfield]]"
    And I should see "[[datefield]]"
    And I should see "[[datetimefield]]"
    And I should see "[[number]]"
    And I should see "[[image]]"
    And I should see "[[file]]"
    And I should see "[[url]]"
    And I should see "[[truefalse]]"
    And I should see "[[menu]]"

    And I should see "Add actions to your record"
    And I should see "**edit**"
    And I should see "**delete**"
    And I should not see "**more**"
    And I should see "**rate**"

    And I should see "Add additional information to your record"
    And I should see "##id##"
    And I should see "##Creator##"
    And I should see "##Creator id##"
    And I should see "##Created##"
    And I should see "##Updater##"
    And I should see "##Updater id##"
    And I should see "##Updated##"
    And I should see "##Group id##"
    And I should see "##Record number##"

    And I should see ">##Record navigation##</div>" in the "textarea#id_header" "css_element"
    And I should see "><tr><td><strong>textfield</strong></td><td>[[textfield]]</td></tr><tr><td><strong>multipletextfield</strong></td><td>[[multipletextfield]]</td></tr><tr><td><strong>datefield</strong></td><td>[[datefield]]</td></tr><tr><td><strong>datetimefield</strong></td><td>[[datetimefield]]</td></tr><tr><td><strong>number</strong></td><td>[[number]]</td></tr><tr><td><strong>image</strong></td><td>[[image]]</td></tr><tr><td><strong>file</strong></td><td>[[file]]</td></tr><tr><td><strong>url</strong></td><td>[[url]]</td></tr><tr><td><strong>truefalse</strong></td><td>[[truefalse]]</td></tr><tr><td><strong>menu</strong></td><td>[[menu]]</td></tr><tr><td colspan=" in the "textarea#id_record" "css_element"

    And I should see "Add comment"
    And I should see "[[Comment]]"
    And I should see "dataplus_comment" in the "textarea#id_comments" "css_element"
    And I should see "dataplus_creator_details" in the "textarea#id_comments" "css_element"
    And I should see "dataplus_comment_cell" in the "textarea#id_comments" "css_element"
    And I should see "[[comment]]" in the "textarea#id_comments" "css_element"
    And I should see "dataplus_comment_actions" in the "textarea#id_comments" "css_element"


    And I should see ">##Record count##</div>" in the "textarea#id_footer" "css_element"
    And I should see ">##Record navigation##</div>" in the "textarea#id_footer" "css_element"

    And I should see "Sort order"
    And the field "id_sortorder1" matches value "N\A"
    And "//span/input[@name='sortoption1']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption1']/following-sibling::label[text()='descending']" "xpath_element" should exist
    And the field "id_sortorder2" matches value "N\A"
    And "//span/input[@name='sortoption2']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption2']/following-sibling::label[text()='descending']" "xpath_element" should exist
    And the field "id_sortorder3" matches value "N\A"
    And "//span/input[@name='sortoption3']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption3']/following-sibling::label[text()='descending']" "xpath_element" should exist
    And the field "id_sortorder4" matches value "N\A"
    And "//span/input[@name='sortoption4']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption4']/following-sibling::label[text()='descending']" "xpath_element" should exist
    And the field "id_sortorder5" matches value "N\A"
    And "//span/input[@name='sortoption5']/following-sibling::label[text()='ascending']" "xpath_element" should exist
    And "//span/input[@name='sortoption5']/following-sibling::label[text()='descending']" "xpath_element" should exist

    And "Save changes" "button" should exist


    # Add/edit record template.

    And I follow "Add/edit record template"

    And "View template" "link" should exist
    And "Single record template" "link" should exist
    And "Add/edit record template" "link" should exist
    And "Help with template hooks" "link" should exist

    And "Show CSS" "button" should exist
    And "Show Javascript" "button" should exist
    And "Reset template" "button" should exist
    And "Enable editor" "button" should exist

    And I should not see "Add record functions"
    And I should not see "##Record count##"
    And I should not see "##Record navigation##"

    And I should see "Add fields to your record"
    And I should see "[[textfield]]"
    And I should see "[[multipletextfield]]"
    And I should see "[[datefield]]"
    And I should see "[[datetimefield]]"
    And I should see "[[number]]"
    And I should see "[[image]]"
    And I should see "[[file]]"
    And I should see "[[url]]"
    And I should see "[[truefalse]]"
    And I should see "[[menu]]"

    And I should see "Add actions to your record"
    And I should see "**save**"
    And I should see "**saveandview**"
    And I should see "**reset**"
    And I should see "**cancel**"
    And I should not see "**edit**"
    And I should not see "**delete**"
    And I should not see "**more**"
    And I should not see "**rate**"

    And I should not see "Add additional information to your record"
    And I should not see "##id##"
    And I should not see "##Creator##"
    And I should not see "##Creator id##"
    And I should not see "##Created##"
    And I should not see "##Updater##"
    And I should not see "##Updater id##"
    And I should not see "##Updated##"
    And I should not see "##Group id##"
    And I should not see "##Record number##"

    And I should see "[[textfield]][[multipletextfield]][[datefield]][[datetimefield]]" in the "textarea#id_record" "css_element"
    And I should see "[[number]][[image]][[file]][[url]][[truefalse]]" in the "textarea#id_record" "css_element"
    And I should see "[[menu]]**save****cancel**" in the "textarea#id_record" "css_element"

    And I should not see "Add comment"
    And I should not see "[[Comment]]"

    And I should not see "Sort order"
    And "//span/input[@name='sortoption1']/following-sibling::label[text()='ascending']" "xpath_element" should not exist
    And "//span/input[@name='sortoption1']/following-sibling::label[text()='descending']" "xpath_element" should not exist
    And "//span/input[@name='sortoption2']/following-sibling::label[text()='ascending']" "xpath_element" should not exist
    And "//span/input[@name='sortoption2']/following-sibling::label[text()='descending']" "xpath_element" should not exist
    And "//span/input[@name='sortoption3']/following-sibling::label[text()='ascending']" "xpath_element" should not exist
    And "//span/input[@name='sortoption3']/following-sibling::label[text()='descending']" "xpath_element" should not exist
    And "//span/input[@name='sortoption4']/following-sibling::label[text()='ascending']" "xpath_element" should not exist
    And "//span/input[@name='sortoption4']/following-sibling::label[text()='descending']" "xpath_element" should not exist
    And "//span/input[@name='sortoption5']/following-sibling::label[text()='ascending']" "xpath_element" should not exist
    And "//span/input[@name='sortoption5']/following-sibling::label[text()='descending']" "xpath_element" should not exist

    And "Save changes" "button" should exist


    # Help with template hooks.

    And I follow "Help with template hooks"

    And "View template" "link" should exist
    And "Single record template" "link" should exist
    And "Add/edit record template" "link" should exist
    And "Help with template hooks" "link" should exist

    And I should see "DataPlus Template Hooks"
    And "Save changes" "button" should not exist


    # Modifications to standard template.

    And I follow "View template"
    And I click on "**more**" "link"
    And I click on "##Updater##" "link"
    And I press "Save changes"

    And I click on "//div/ul/li/a[@title='View'][text()='View']" "xpath_element"
    And "more" "link" should exist
    And "//div[@class='dataplus_record'][text()='Teacher 1']" "xpath_element" should exist

    And I follow "Templates"
    And I press "Reset template"
    And I press "Save changes"

    And I click on "//div/ul/li/a[@title='View'][text()='View']" "xpath_element"
    And "more" "link" should not exist
    And "//div[@class='dataplus_record'][text()='Teacher 1']" "xpath_element" should not exist


    # View and amend Template CSS.

    And I follow "Templates"
    And I press "Show CSS"

    And I should see "CSS template"
    And I should see "div.dataplus_record, div.dataplus_comment{"
    And I should see "margin-bottom: 2em;"
    And I should see "}"

    And the dataplus textarea for "CSS template" is set with the following lines:
      | div.dataplus_record_count{  |
      | display: none;              |
      | }                           |
    And I press "Save changes"
    And I click on "//div/ul/li/a[@title='View'][text()='View']" "xpath_element"
    And I should not see "records 1 - 3 of 3"


    # Add Template JavaScript.

    And I follow "Templates"
    And I press "Show Javascript"

    And the dataplus textarea for "Javascript functions to run when page loads (leave blank if unsure)" is set with the following lines:
      | var x = document.querySelectorAll  |
      |("img[alt = 'image alt']");         |
      | x[0].className = "behattestclass"; |
    And I press "Save changes"
    And I click on "//div/ul/li/a[@title='View'][text()='View']" "xpath_element"
    And "//div[@class='dataplus_record']//img[@class='behattestclass']" "xpath_element" should exist


    # Enable and Disable editor.

    And I follow "Templates"
    And I press "Enable editor"
    And "//div[@class='editor_atto_toolbar']/div[13]" "xpath_element" should exist

    And I press "Disable editor"
    And "//div[@class='editor_atto_toolbar']" "xpath_element" should not exist

