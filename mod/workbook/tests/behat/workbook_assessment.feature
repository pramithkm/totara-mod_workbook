@mod @mod_workbook @javascript
Feature: Workbook submission and assessment
  In order to use workbook activity
  As a student
  I need to be able to view workbook content and submit answers to questions

  Background:
    Given the following "users" exists:
      | username | firstname    | lastname | email            |
      | student1 | Eugene1      | Student1 | student1@asd.com |
      | student2 | Eugene2      | Student2 | student2@asd.com |
      | student3 | Eugene3      | Student3 | student3@asd.com |
      | student4 | Eugene4      | Student4 | student3@asd.com |
      | teacher1 | Elmaret1     | Teacher1 | teacher1@asd.com |
      | teacher2 | Elmaret2     | Teacher2 | teacher2@asd.com |
      | teacher3 | Elmaret3     | Teacher3 | teacher3@asd.com |
    And the following "courses" exists:
      | fullname  | shortname |
      | Course 1   | c1        |
    And the following "course enrolments" exists:
      | user     | course | role           |
      | student1 | c1     | student        |
      | student2 | c1     | student        |
      | student3 | c1     | student        |
      | student4 | c1     | student        |
      | teacher1 | c1     | editingteacher |
      | teacher2 | c1     | editingteacher |
      | teacher3 | c1     | editingteacher |
    And the following "groups" exist:
      | name | course   | idnumber |
      | group1 | c1       | group1 |
      | group2 | c1       | group2 |
    And the following "group members" exist:
      | user        | group   |
      | student1    | group1  |
      | student2    | group1  |
      | student3    | group2  |
      | student4    | group2  |
      | teacher1    | group1  |
      | teacher2    | group2  |
    And I log in as "admin"
    And I follow "Course 1"
    And I turn editing mode on
    # Configure workbook and pages
    And I add a "Workbook" to section "1" and I fill the form with:
      | Workbook name  | Separate groups workbook       |
      | Group mode  | Separate groups                   |
    And I follow "Separate groups workbook"
    And I follow "Edit pages"
    # Add a page with a static content item
    And I press "Add a page"
    And I fill the moodle form with:
      | Title | The Long Page 1 |
      | Navigation title | Page 1 navtitle    |
    And I press "Save changes"
    Then I should see "The Long Page 1"
    And I click on "Add page item" "link" in the ".config-mod-workbook-page[pageid=1]" "css_element"
    And I fill the moodle form with:
      | Item name       | Page item 1                                           |
      | Content         | This a static page item type for some static content  |
      | Allow comments  | 1                                                     |
    Then the "Required grade" "field" should be disabled
    And I press "Save changes"
    Then I should see "This a static page item type for some static content"
    # Add another page with a longtext question
    And I press "Add a page"
    And I fill the moodle form with:
      | Title | The Long Page 2 |
      | Navigation title | Page 2 navtitle    |
    And I press "Save changes"
    Then I should see "The Long Page 2"
    And I click on "Add page item" "link" in the ".config-mod-workbook-page[pageid=2]" "css_element"
    And I fill the moodle form with:
      | Type            | Essay question                    |
      | Item name       | Page item 2                       |
      | Content         | This the first question page item |
      | Required grade  | 100                               |
    And I press "Save changes"
    Then I should see "This the first question page item"
    # Add a subpage with a longtext question
    And I press "Add a page"
    And I fill the moodle form with:
      | Title               | The Long SubPage 3 |
      | Navigation title    | Page 3 subnavtitle |
      | Parent page         | The Long Page 2    |
    And I press "Save changes"
    Then I should see "The Long SubPage 3"
    And I click on "Add page item" "link" in the ".config-mod-workbook-page[pageid=3]" "css_element"
    And I fill the moodle form with:
      | Type            | Essay question                        |
      | Item name       | Page item 3                           |
      | Content         | This the second question page item    |
      | Required grade  | 50                                    |
    And I press "Save changes"
    Then I should see "This the second question page item"
    And I log out


# @javascript
  Scenario: Submit and assess workbook
    # Submit the first question as student1
    When I log in as "student1"
    And I follow "Course 1"
    Then I should see "Separate groups workbook"
    And I follow "Separate groups workbook"
    # Check first page
    Then I should see "Page 1 navtitle"
    Then I should see "Page 2 navtitle"
    Then I should see "Page 3 subnavtitle"
    Then I should see "The Long Page 1"
    Then I should see "This a static page item type for some static content"
    Then I should see "Comments"
    # Check navigation
    And I click on ".workbook-nav-pages div[pageid=2]" "css_element"
    And I fill in "workbook-essay-response" with "Eugene is pretty boss"
    # Check bottom navigation
    And I click on ".mod-workbook-nav-next" "css_element"
    Then I should see "The Long SubPage 3"
    And I click on ".mod-workbook-nav-prev" "css_element"
    # Check that instant save worked
    Then I should see "Eugene is pretty boss"
    Then I should see "Modified on"
    Then I should see "Status: Draft"
    # Now submit for assessment
    And I click on "Submit for assessment" "button" confirming the dialogue
    Then I should see "Status: Submitted"
    Then I should see "Not graded"
    And I log out

    # Submit the last question as student3 (different group)
    When I log in as "student3"
    And I follow "Course 1"
    And I follow "Separate groups workbook"
    And I click on ".mod-workbook-nav-next" "css_element"
    And I click on ".mod-workbook-nav-next" "css_element"
    And I fill in "workbook-essay-response" with "Harties is in the valley!"
    And I click on "Submit for assessment" "button" confirming the dialogue
    Then I should see "Status: Submitted"
    And I log out

    # Log in as admin and trigger cron so the submission notifications go out
    When I log in as "admin"
    And I trigger cron
    And I am on homepage
    And I log out

    # Log in as teacher1 and assess
    When I log in as "teacher1"
    # Check notifications
    And I click on "My Learning" in the totara menu
    Then I should see "Essay question submitted by Eugene1"
    Then I should not see "Essay question submitted by Eugene3"
    # Check submission
    And I follow "Home"
    And I follow "Course 1"
    And I follow "Separate groups workbook"
    And I follow "Assess submissions"
    Then I should see "Eugene1 Student1"
    Then I should see "Page item 2"
    Then I should not see "Eugene2 Student2"
    And I click on "Assess" "link" in the "table.reportbuilder-table" "css_element"
    Then I should see "Eugene1 Student1"
    Then I should see "Status: Submitted"
    # Not meeting grade
    And I wait until ".mod-workbook-submission-grade input[name=workbook-submission-grade-input]" "css_element" exists
    And I fill in "workbook-submission-grade-input" with "7"
    And I click on ".mod-workbook-nav-next" "css_element"
    And I click on ".mod-workbook-nav-prev" "css_element"
    Then I should see "Status: Graded"
    And I wait until ".mod-workbook-submission-grade input[name=workbook-submission-grade-input]" "css_element" exists
    And I fill in "workbook-submission-grade-input" with "777"
    And I click on ".mod-workbook-nav-next" "css_element"
    And I click on ".mod-workbook-nav-prev" "css_element"
    Then I should see "Status: Finished"
    Then I should see "by Elmaret1"
    # Finished submissions should not be gradable any more.
    Then ".mod-workbook-submission-grade input" "css_element" should not exists
    # Ensure no grading is possible for unfinished items:
    # student1 has not completed the second question
    And I click on ".mod-workbook-nav-next" "css_element"
    Then ".mod-workbook-submission-grade input" "css_element" should not exists
    And I log out

    # Log in as admin and trigger cron so the assessment notifications go out
    When I log in as "admin"
    And I trigger cron
    And I am on homepage
    And I log out

    # Finally, log in as student1 and check notification
    When I log in as "student1"
    # Check notifications
    And I click on "My Learning" in the totara menu
    And I pause scenario execution
    Then I should see "Your Essay question submission has been assessed"
    And I log out

    # Teacher in no groups should be seeing all submissions
    When I log in as "teacher3"
    And I follow "Course 1"
    And I follow "Separate groups workbook"
    And I follow "Assess submissions"
    Then I should see "Eugene1 Student1"
    Then I should see "Page item 2"
    Then I should see "Eugene3 Student3"
    Then I should see "Page item 3"
    And I log out

