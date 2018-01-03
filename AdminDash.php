<?php
// Set the namespace defined in your config file
namespace UIOWA\AdminDash;

// The next 2 lines should always be included and be the same in every module
use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

// Declare your module class, which must extend AbstractExternalModule
class AdminDash extends AbstractExternalModule {
    // Your module methods, constants, etc. go here

    public static $reportReference = array
    (
        array // Projects by User
        (
            "reportName" => "Projects by User",
            "fileName" => "projectsByUser",
            "description" => "List of users and the projects to which they have access.",
            "tabIcon" => "fa fa-male",
            "sql" => "
        SELECT
        info.username AS 'HawkID',
        CONCAT(info.user_lastname, ', ', info.user_firstname) AS 'User Name',
        info.user_email AS 'User Email',
        GROUP_CONCAT(CAST(project.project_id AS CHAR(50)) SEPARATOR ', ') AS 'Project Titles',
        GROUP_CONCAT(CAST(CASE project.status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE project.status
        END AS CHAR(50))) AS 'Project Statuses (Hidden)',
        GROUP_CONCAT(CAST(CASE WHEN project.date_deleted IS NULL THEN 'N/A'
        ELSE project.date_deleted
        END AS CHAR(50))) AS 'Project Deleted Date (Hidden)',
        COUNT(project.project_id) AS 'Projects Total'
        FROM redcap_user_information AS info,
        redcap_projects AS project,
        redcap_user_rights AS access
        WHERE info.username = access.username AND
        access.project_id = project.project_id
        GROUP BY info.ui_id
        ORDER BY info.user_lastname, info.user_firstname, info.username
        "
       ),
        array // Users by Project
        (
            "reportName" => "Users by Project",
            "fileName" => "usersByProject",
            "description" => "List of projects and the users which have access.",
            "tabIcon" => "fa fa-folder",
            "sql" => "
        SELECT
        redcap_projects.project_id AS PID,
        app_title AS 'Project Name',
        CAST(CASE status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE status
        END AS CHAR(50)) AS 'Status',
        GROUP_CONCAT(CAST(CASE WHEN redcap_projects.date_deleted IS NULL THEN 'N/A'
        ELSE redcap_projects.date_deleted
        END AS CHAR(50))) AS 'Project Deleted Date (Hidden)',
        record_count AS 'Record Count',
        CAST(CASE purpose
        WHEN 0 THEN 'Practice / Just for fun'
        WHEN 1 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 4 THEN 'Other'
        ELSE purpose
        END AS CHAR(50)) AS 'Purpose',
        GROUP_CONCAT((redcap_user_rights.username) SEPARATOR ', ') AS 'Project Users',
        DATE_FORMAT(last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
        DATEDIFF(now(), last_logged_event) AS 'Days Since Last Event',
        COUNT(redcap_user_rights.username) AS 'Users Total'
        FROM redcap_projects
        LEFT JOIN redcap_record_counts ON redcap_projects.project_id = redcap_record_counts.project_id
        LEFT JOIN redcap_user_rights ON redcap_projects.project_id = redcap_user_rights.project_id
        GROUP BY redcap_projects.project_id
        ORDER BY app_title
        "
       ),
        array // Research Projects
        (
            "reportName" => "Research Projects",
            "fileName" => "researchProjects",
            "description" => "List of projects that are identified as being used for research purposes.",
            "tabIcon" => "fa fa-flask",
            "sql" => "
        SELECT
        redcap_projects.project_id AS PID,
        app_title AS 'Project Name',
        CAST(CASE status
        WHEN 0 THEN 'Development'
        WHEN 1 THEN 'Production'
        WHEN 2 THEN 'Inactive'
        WHEN 3 THEN 'Archived'
        ELSE status
        END AS CHAR(50)) AS 'Status',
        CAST(CASE WHEN redcap_projects.date_deleted IS NULL THEN 'N/A'
        ELSE redcap_projects.date_deleted
        END AS CHAR(50)) AS 'Project Deleted Date (Hidden)',
        record_count AS 'Record Count',
        purpose_other AS 'Purpose Specified',
        CONCAT(project_pi_lastname, ', ', project_pi_firstname, ' ', project_pi_mi) AS 'PI Name',
        project_pi_email AS 'PI Email',
        project_irb_number AS 'IRB Number',
        DATE_FORMAT(last_logged_event, '%Y-%m-%d') AS 'Last Logged Event Date',
        DATEDIFF(now(), last_logged_event) AS 'Days Since Last Event'
        FROM redcap_projects
        LEFT JOIN redcap_record_counts ON redcap_projects.project_id = redcap_record_counts.project_id
        WHERE purpose = 2  -- 'Research'
        ORDER BY app_title
        "
       ),
        array // Development Projects
        (
            "reportName" => "Development Projects",
            "fileName" => "developmentProjects",
            "description" => "List of record counts for projects in Development Mode.",
            "tabIcon" => "fa fa-folder",
            "sql" => "
        SELECT
        redcap_projects.project_id AS 'PID',
        app_title AS 'Project Name',
        project_pi_email AS 'PI Email',
        record_count AS 'Record Count',
        CAST(CASE purpose
        WHEN 0 THEN 'Practice / Just for fun'
        WHEN 1 THEN 'Operational Support'
        WHEN 2 THEN 'Research'
        WHEN 3 THEN 'Quality Improvement'
        WHEN 4 THEN 'Other'
        ELSE purpose
        END AS CHAR(50)) AS 'Purpose',
        CAST(CASE WHEN redcap_projects.date_deleted IS NULL THEN 'N/A'
        ELSE redcap_projects.date_deleted
        END AS CHAR(50)) AS 'Project Deleted Date (Hidden)',
        creation_time AS 'Creation Time',
        last_logged_event AS 'Last Logged Event'
        FROM redcap_projects
        INNER JOIN redcap_record_counts ON redcap_projects.project_id = redcap_record_counts.project_id
        WHERE (redcap_projects.status = 0 and redcap_projects.purpose != 0)
        "
       ),
        array // Passwords in Project Titles
        (
            "reportName" => "Passwords in Project Titles",
            "fileName" => "projectPassword",
            "description" => "List of projects that contain strings related to REDCap login credentials (usernames/passwords) in the project title.",
            "tabIcon" => "fa fa-key",
            "sql" => "
        SELECT projects.project_id AS 'PID',
        app_title AS 'Project Name'
        FROM redcap_projects AS projects,
        redcap_user_information AS users
        WHERE (projects.created_by = users.ui_id) AND
        ((app_title LIKE '%pass%word%') OR
        (app_title LIKE '%pass%wd%') OR
        (app_title LIKE '%hawk%id%') OR
        (app_title LIKE '%user%name%') OR
        (app_title LIKE '%user%id%'));
        "
       ),
        array // Passwords in Instruments
        (
            "reportName" => "Passwords in Instruments",
            "fileName" => "instrumentPassword",
            "description" => "List of projects that contain strings related to REDCap login credentials (usernames/passwords) in the instrument or form name.",
            "tabIcon" => "fa fa-key",
            "sql" => "
        SELECT projects.project_id AS 'PID',
        projects.app_title AS 'Project Name',
        meta.form_menu_description AS 'Instrument Name'
        FROM redcap_projects AS projects,
        redcap_metadata AS meta,
        redcap_user_information AS users
        WHERE (projects.created_by = users.ui_id) AND
        (projects.project_id = meta.project_id) AND
        (meta.form_menu_description IS NOT NULL) AND
        ((app_title LIKE '%pass%word%') OR
        (app_title LIKE '%pass%wd%') OR
        (app_title LIKE '%hawk%id%') OR
        (app_title LIKE '%user%name%') OR
        (app_title LIKE '%user%id%'));
              "
       ),
        array // Passwords in Fields
        (
            "reportName" => "Passwords in Fields",
            "fileName" => "fieldPassword",
            "description" => "List of projects that contain strings related to REDCap login credentials (usernames/passwords) in one of the fields.",
            "tabIcon" => "fa fa-key",
            "sql" => "
        SELECT projects.project_id AS 'PID',
        projects.app_title AS 'Project Name',
        meta.form_name AS 'Form Name',
        meta.field_name AS 'Field Name',
        meta.element_label AS 'Field Label',
        meta.element_note AS 'Field Note'
        FROM redcap_projects AS projects,
        redcap_metadata AS meta,
        redcap_user_information AS users
        WHERE (projects.created_by = users.ui_id) AND
        (projects.project_id = meta.project_id) AND
        ((field_name LIKE '%pass%word%') OR
        (field_name LIKE '%pass%wd%') OR
        (field_name LIKE '%hawk%id%') OR
        (field_name LIKE '%hwk%id%') OR
        (field_name LIKE '%user%name%') OR
        (field_name LIKE '%user%id%') OR
        (field_name LIKE '%usr%name%') OR
        (field_name LIKE '%usr%id%') OR
        (element_label LIKE '%pass%word%') OR
        (element_label LIKE '%pass%wd%') OR
        (element_label LIKE '%hawk%id%') OR
        (element_label LIKE '%hwk%id%') OR
        (element_label LIKE '%user%name%') OR
        (element_label LIKE '%user%id%') OR
        (element_label LIKE '%usr%name%') OR
        (element_label LIKE '%usr%id%') OR
        (element_note LIKE '%pass%word%') OR
        (element_note LIKE '%pass%wd%') OR
        (element_note LIKE '%hawk%id%') OR
        (element_note LIKE '%hwk%id%') OR
        (element_note LIKE '%user%name%') OR
        (element_note LIKE '%user%id%') OR
        (element_note LIKE '%usr%name%') OR
        (element_note LIKE '%usr%id%'))
        ORDER BY projects.project_id, form_name, field_name;
        "
       ),
        array // Visualizations
        (
            "reportName" => "Visualizations",
            "fileName" => "visualizations",
            "description" => "Additional data presented in graph and chart form.",
            "tabIcon" => "fa fa-pie-chart"
       )
   );

    public static $visualizationQueries = array
    (
    array
    (
    "visName" => "\"Status (All Projects)\"",
    "visID" => "\"status_all\"",
    "visType" => "\"count\"",
    "countColumns" => ["Status"],
    "sql" => "
            SELECT
            CAST(CASE status
            WHEN 0 THEN 'Development'
            WHEN 1 THEN 'Production'
            WHEN 2 THEN 'Inactive'
            WHEN 3 THEN 'Archived'
            ELSE status
            END AS CHAR(50)) AS 'Status'
            FROM redcap_projects
            "
   ),
    array
    (
    "visName" => "\"Purpose Specified (Research Projects)\"",
    "visID" => "\"purpose_all\"",
    "visType" => "\"count\"",
    "countColumns" => ["Purpose"],
    "sql" => "
            SELECT
            CAST(CASE purpose
            WHEN 0 THEN 'Practice / Just for fun'
            WHEN 1 THEN 'Operational Support'
            WHEN 2 THEN 'Research'
            WHEN 3 THEN 'Quality Improvement'
            WHEN 4 THEN 'Other'
            ELSE purpose
            END AS CHAR(50)) AS 'Purpose'
            FROM redcap_projects
            "
   )
   );

    public static $miscQueryReference = array
    (
    array
    (
    "queryName" => "Suspended Users",
    "sql" => "
            SELECT count(*) FROM redcap_user_information WHERE user_suspended_time IS NOT NULL
            "
   )
   );

    public function generateAdminDash() {
        ?>
        <script src="<? print $this->getUrl("/resources/tablesorter/jquery-3.2.0.min.js") ?>"></script>
        <script src="<? print $this->getUrl("/resources/tablesorter/jquery.tablesorter.min.js") ?>"></script>
        <script src="<? print $this->getUrl("/resources/tablesorter/jquery.tablesorter.widgets.min.js") ?>"></script>
        <script src="<? print $this->getUrl("/resources/tablesorter/widgets/widget-pager.min.js") ?>"></script>
        <script src="<? print $this->getUrl("/resources/c3/d3.min.js") ?>" charset="utf-8"></script>
        <script src="<? print $this->getUrl("/resources/c3/c3.min.js") ?>"></script>

        <link href="<? print $this->getUrl("/resources/tablesorter/tablesorter/theme.blue.min.css") ?>" rel="stylesheet">
        <link href="<? print $this->getUrl("/resources/tablesorter/tablesorter/jquery.tablesorter.pager.min.css") ?>" rel="stylesheet">
        <link href="<? print $this->getUrl("/resources/c3/c3.css") ?>" rel="stylesheet" type="text/css">
        <link href="<? print $this->getUrl("/resources/font-awesome-4.7.0/css/font-awesome.min.css") ?>" rel="stylesheet">
        <link href="<? print $this->getUrl("/resources/styles.css") ?>" rel="stylesheet" type="text/css"/>

        <script src="<? print $this->getUrl("/adminDash.js") ?>"></script>
        <?php

        // display the header
        $HtmlPage = new \HtmlPage();
        $HtmlPage->PrintHeaderExt();

        // define variables
        $title = $this->getModuleName();
        $pageInfo = self::$reportReference[ (!$_REQUEST['tab']) ? 0 : $_REQUEST['tab'] ];
        $csvFileName = sprintf("%s.%s.csv", $pageInfo['fileName'], date("Y-m-d_His"));

        // only allow super users to view this information
        if (!SUPER_USER) die("Access denied! Only super users can access this page.");

        // start the stopwatch ...
        $this->elapsedTime();

        if (!$pageInfo['sql']) :
         foreach (self::$visualizationQueries as $vis => $visInfo):
            ?>
               <script>
               d3.json("<? print $this->getUrl("getGraphData.php?vis=" . $vis) ?>", function (error, json) {
                   console.log(json);

                   if (error) return console.warn(error);

                    UIOWA_AdminDash.createPieChart(
                      UIOWA_AdminDash.getCountsFromJson(
                          json,
                          <?php echo json_encode($visInfo['countColumns']) ?>
                    ),
                     <?= $visInfo['visName'] ?>,
                     <?= $visInfo['visID'] ?>
                   );
               });
               </script>
            <?php
         endforeach;
        endif;

        ?>
         <h2 style="text-align: center; color: #800000; font-weight: bold;">
             <?= $title ?>
             </h2>

             <p />

                <!-- create navigation tabs -->
             <ul class='nav nav-tabs' role='tablist'>
             <?php foreach(self::$reportReference as $report => $reportInfo): ?>
             <li <?= $_REQUEST['tab'] == $report ? "class=\"active\"" : "" ?> >
             <a href="<?= $this->formatUrl($report) ?>">
             <span class="<?= $reportInfo['tabIcon'] ?>"></span>&nbsp; <?= $reportInfo['reportName'] ?></a>
         </li>
        <?php endforeach; ?>
         </ul>

         <p />

         <!-- display csv download button (for reports) -->
        <?php if($pageInfo['sql']) : ?>
        <div style="text-align: right; width: 100%">
            <a href="<? print $this->getUrl("downloadCsvViaSql.php?tab=" . $_REQUEST['tab'] . "&file=" . $csvFileName) ?>"
               class="btn btn-default btn-lg">
          <span class="fa fa-download"></span>&nbsp;
        Download CSV File</a>
        </div>
        <?php endif; ?>

        <p />

        <h3 style="text-align: center">
          <?= $pageInfo['reportName'] ?>
          </h3>

          <h5 style="text-align: center">
          <?= $pageInfo['description']; ?>
          </h5>

<!--        <input id="hideDeleted" type="checkbox" class="checkbox" style="text-align: center"> Hide Deleted Projects</input>-->

        <?php if($pageInfo['sql']) : ?>
             <!-- display tablesorter pager buttons for reports -->
          <div id="pager" class="pager">
          <form>
          <img src="<? print $this->getUrl("resources/tablesorter/tablesorter/images/icons/first.png") ?>" class="first"/>
          <img src="<? print $this->getUrl("resources/tablesorter/tablesorter/images/icons/prev.png") ?>" class="prev"/>
             <!-- the "pagedisplay" can be any element, including an input -->
          <span class="pagedisplay" data-pager-output-filtered="{startRow:input} &ndash; {endRow} / {filteredRows} of {totalRows} total rows"></span>
          <img src="<? print $this->getUrl("resources/tablesorter/tablesorter/images/icons/next.png") ?>" class="next"/>
          <img src="<? print $this->getUrl("resources/tablesorter/tablesorter/images/icons/last.png") ?>" class="last"/>
          <select class="pagesize">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
          <option value="all">All Rows</option>
        </select>
        </form>
        </div>
        <?php else : ?>
         <!-- display graphs, ids must match above -->
        <div style="display: inline-block; margin: 0 auto;">
          <div style="width: 100%; display: table; max-height: 500px;">
          <div style="display: table-row">
          <?php foreach (self::$visualizationQueries as $vis => $visInfo): ?>
          <div style="width: 500px; display: table-cell;" id=<?= $visInfo['visID'] ?>></div>
        <?php endforeach; ?>
        </div>
        <div style="display: table-row">
          <div style="display: table-cell" id="status_research"></div>
          </div>
          </div>
          </div>

        <?php endif; ?>

        <?php
        // display normal reports
        if($pageInfo['sql']) {
         // execute the SQL statement
         $result = $this->sqlQuery($pageInfo['sql']);

          $this->formatQueryResults($result, "html");

         printf("   </tbody>\n");
         printf("</table>\n");  // <table> created by PrintTableHeader

         if ($_REQUEST['tab'] == 0) {
            $result = db_query(self::$miscQueryReference[0]['sql']);
            printf($this->formatQueryResults($result, "text") . " users are currently suspended.");
         }
        }

        $this->displayElapsedTime();

        // Display the footer
        $HtmlPage->PrintFooterExt();
   }

    public function formatQueryResults($result, $format)
    {
        $hideArchivedProjects = FALSE;
        $hideDeletedProjects = FALSE;

        $redcapProjects = $this->getRedcapProjectNames();
        $isFirstRow = TRUE;

        if ($result -> num_rows == 0) {
            printf("No records found.");
        }

        while ($row = db_fetch_assoc($result))
        {

            if ($hideArchivedProjects & $row['Status'] == 'Archived') {
                continue;
            }
            if ($hideDeletedProjects) {
                if ($_REQUEST['tab'] == 0) {
                    unset($row['Project Deleted Date (Hidden)']);
                }
                else {
                    continue;
                }
            }

            if ($isFirstRow) {
                // use column aliases for column headers
                $headers = array_keys($row);

                // remove any columns marked as hidden
                $searchword = '(Hidden)';
                $hiddenColumns = array_filter($headers, function($var) use ($searchword) { return preg_match("/\b$searchword\b/i", $var); });

                foreach ($hiddenColumns as $column)
                {
                    $index = array_search($column, $headers);
                    unset($headers[$index]);
                }
            }
            if ($format == 'html') {
                if ($isFirstRow)
                {
                    // print table header
                    $this->printTableHeader($headers);
                    printf("   <tbody>\n");
                    $isFirstRow = FALSE;  // toggle flag
                }

                $webData = $this->webifyDataRow($row, $redcapProjects);
                $this->printTableRow($webData, $hiddenColumns);
            }
            elseif ($format == 'csv') {
                if ($isFirstRow)
                {
                    $headerStr = implode("\",\"", $headers);
                    printf("\"%s\"\n", $headerStr);

                    $isFirstRow = FALSE;  // toggle flag
                }

                foreach ($hiddenColumns as $column)
                {
                    unset($row[$column]);
                }

                $rowStr = implode("\",\"", $row);
                $row['Purpose Specified'] = $this->convertProjectPurpose2List($row['Purpose Specified']);

                printf("\"%s\"\n", $rowStr);
            }
            elseif ($format == 'text') {
                $rowStr = implode("\",\"", $row);
                return $rowStr;
            }
        }
    }

    private function printTableHeader($columns)
    {
        printf("
<table id='reportTable' class='tablesorter'>
   <thead>
      <tr>\n", 'reportTable');

        foreach ($columns as $name)
            printf("         <th> %s </th>\n", $name);

        printf("
      </tr>
   </thead>\n");

    }

    private function printTableRow($row, $hiddenColumns)
    {
        printf("      <tr>\n");

        foreach ($row as $key => $value)
        {
            if (!array_search($key, $hiddenColumns)) {
                printf("         <td> %s </td>\n", $value);
            }
        }

        printf("      </tr>\n");
    }

    private function webifyDataRow($row, $projectTitles)
    {
        // initialize value
        $webified = array();

        foreach ($row as $key => $value)
        {
            if ($key == "PID")
            {
                $projectStatus = $row['Status'];
                $projectDeleted = $row['Project Deleted Date (Hidden)'];

                $webified[$key] = $this->convertPid2Link($value, $value, $projectStatus, $projectDeleted);
            }
            elseif ($key == "Project Name")
            {
                $pid = $row['PID'];
                $hrefStr = $row['Project Name'];
                $projectStatus = $row['Status'];
                $projectDeleted = $row['Project Deleted Date (Hidden)'];

                $webified[$key] = $this->convertPid2Link($pid, $hrefStr, $projectStatus, $projectDeleted);
            }
            elseif ($key == "Name of Project")
            {
                $pid = $row['PID'];
                $hrefStr = $row['Name of Project'];

                $webified[$key] = $this->convertPid2AdminLink($pid, $hrefStr);
            }
            elseif ($key == "Project Titles")
            {
                $projectStatuses = $row['Project Statuses (Hidden)'];
                $projectDeleted = $row['Project Deleted Date (Hidden)'];

                $webified[$key] = $this->convertPidList2Links($value, $projectTitles, $projectStatuses, $projectDeleted);
            }
            elseif ($key == "Purpose Specified")
            {
                $webified[$key] = $this->convertProjectPurpose2List($value);
            }
            elseif (($key == "PI Email") ||
                ($key == "Owner Email") ||
                ($key == "User Email"))
            {
                $webified[$key] = $this->convertEmail2Link($value);
            }
            elseif (($key == "Owner HawkID") ||
                ($key == "Project Users") ||
                ($key == "HawkID"))
            {
                $webified[$key] = $this->convertUsername2Link($value);
            }
            else
            {
                $webified[$key] = $value;
            }
        }

        return($webified);
    }

    private function convertEmail2Link($email)
    {
        $mailtoLink = sprintf("<a href=\"mailto:%s\">%s</a>",
            $email, $email);

        return($mailtoLink);
    }

    private function convertPid2Link($pid, $hrefStr, $projectStatus, $projectDeleted)
    {
        $urlString =
            sprintf("https://%s%sProjectSetup/index.php?pid=%d",  // Project Setup page
                SERVER_NAME,  // www-dev.icts.uiowa.edu
                APP_PATH_WEBROOT, // /redcap/redcap_v5.10.0/
                $pid);  // 15

        $styleIdStr = '';

        if ($projectStatus == 'Archived') {
            $styleIdStr = "id=archived";
        }
        if ($projectDeleted != 'N/A') {
            $styleIdStr = "id=deleted";
        }
        $pidLink = sprintf("<a href=\"%s\"
                          target=\"_blank\"" . $styleIdStr . ">%s</a>",
            $urlString, $hrefStr);

        return($pidLink);
    }

    private function convertPid2AdminLink($pid, $hrefStr)
    {
        // https://www-dev.icts.uiowa.edu/redcap/redcap_v6.1.4/ControlCenter/edit_project.php?project=15
        $urlString =
            sprintf("https://%s%sControlCenter/edit_project.php?project=%d",  // Project Setup page
                SERVER_NAME,  // www-dev.icts.uiowa.edu
                APP_PATH_WEBROOT, // /redcap/redcap_v5.10.0/
                $pid);  // 15

        $pidLink = sprintf("<a href=\"%s\"
                          target=\"_blank\">%s</a>",
            $urlString, $hrefStr);

        return($pidLink);
    }

    private function convertPidList2Links($pidStr, $pidTitles, $projectStatuses, $projectDeleted)
    {
        // convert comma-delimited string to array
        $pidList = explode(", ", $pidStr);
        $pidLinks = array();

        $statusList = explode(",", $projectStatuses);
        $deletedList = explode(",", $projectDeleted);

        foreach ($pidList as $index=>$pid)
        {
            $hrefStr = $pidTitles[$pid];
            array_push($pidLinks, $this->convertPid2Link($pid, $hrefStr, $statusList[$index], $deletedList[$index]));
        }

        // convert array back to comma-delimited string
        $pidCell = implode("<br />", $pidLinks);

        return($pidCell);
    }

    private function convertUsername2Link($userIDs)
    {
        // convert comma delimited string to array
        $userIDlist = explode(", ", $userIDs);
        $linkList = array();

        foreach ($userIDlist as $userID)
        {
            $urlString =
                sprintf("https://%s%sControlCenter/view_users.php?username=%s",  // Browse User Page
                    SERVER_NAME,
                    APP_PATH_WEBROOT,
                    $userID);

            $userLink = sprintf("<a href=\"%s\"
                              target=\"_blank\">%s</a>",
                $urlString, $userID);

            array_push($linkList, $userLink);
        }

        // convert array to comma delimited string
        $linkStr = implode( "<br>", $linkList);

        return($linkStr);
    }

    private function convertProjectPurpose2List($purposeList)
    {
        // initialize variables
        $purposeResults = array();
        $purposeParts = explode(",", $purposeList);
        $purposeMaster = array("Basic or Bench Research",
            "Clinical Research Study or Trial",
            "Translational Research 1",
            "Translational Research 2",
            "Behavioral or Psychosocial Research Study",
            "Epidemiology",
            "Repository",
            "Other");

        foreach ($purposeParts as $index)
        {
            array_push($purposeResults, $purposeMaster[$index]);
        }

        $purposeStr = implode(", ", $purposeResults);

        return($purposeStr);
    }

    private function elapsedTime()
    {
        // initialize variables
        static $startTime = null;
        $elapseTimeStr = "";

        if ($startTime == null)  // start the clock
        {
            $startTime = round(microtime(true));
            // printf("\$startTime: %f<br />", $startTime);
        }
        else
        {
            $endTime = round(microtime(true));
            // printf("\$endTime: %f<br />", $endTime);
            $elapseTime = $endTime - $startTime;
            // printf("\$elapsedTime: %f<br />", $elapsedTime);

            $elapseTimeStr = date("i:s", $elapseTime);
        }

        return($elapseTimeStr);
    }

    private function sqlQuery($query)
    {
        // execute the SQL statement
        $result = db_query($query);

        if (! $result)  // sql failed
        {
            $message = printf("Line: %d<br />
                          Could not execute SQL<br />
                          Error #: %d<br />
                          Error Msg: %s",
                __LINE__);
            die($message);
        }
        else
        {
            return $result;
        }
    }

    private function displayElapsedTime()
    {
        $load = sys_getloadavg();

        printf("<div id='elapsedTime'>
            Elapsed Execution Time: %s<br />
            System load avg last minute: %d%%<br />
            System load avg last 5 mins: %d%%<br />
            System load avg last 15 min: %d%%</div>",
            $this->elapsedTime(), $load[0] * 100, $load[1] * 100, $load[2] * 100);
    }

    private function getRedcapProjectNames()
    {
        if (SUPER_USER)
        {
            $sql = "SELECT project_id AS pid,
                     TRIM(app_title) AS title
              FROM redcap_projects
              ORDER BY pid";
        }
        else
        {
            $sql = sprintf("SELECT p.project_id AS pid,
                              TRIM(p.app_title) AS title
                       FROM redcap_projects p, redcap_user_rights u
                       WHERE p.project_id = u.project_id AND
                             u.username = '%s'
                       ORDER BY pid", USERID);
        }

        $query = db_query($sql);

        if (! $query)  // sql failed
        {
            die("Could not execute SQL:
            <pre>$sql</pre> <br />");
        }

        $projectNameHash = array();

        while ($row = db_fetch_assoc($query))
        {
            // $value = strip_tags($row['app_title']);
            $key = $row['pid'];
            $value = $row['title'];

            if (strlen($value) > 80)
            {
                $value = trim(substr($value, 0, 70)) . " ... " .
                    trim(substr($value, -15));
            }

            if ($value == "")
            {
                $value = "[Project title missing]";
            }

            $projectNameHash[$key] = $value;
        }

        return($projectNameHash);

    }

    private function formatUrl($tab)
    {
        $query = $_GET;
        $query['tab'] = $tab;

        $url = http_build_query($query);
        $url = $_SERVER['PHP_SELF'] . '?' . $url;

        return ($url);
    }
}
?>