<?php

include("../config/database.php");

/* AUTO OVERDUE DETECTION */

$query = "

UPDATE employee_tasks

JOIN tasks

ON employee_tasks.task_id = tasks.id

SET employee_tasks.status='Overdue'

WHERE tasks.end_date < CURDATE()

AND employee_tasks.status != 'Completed'

";

mysqli_query($conn, $query);

mysqli_query($conn, "
UPDATE employee_tasks
JOIN tasks
ON employee_tasks.task_id = tasks.id
SET employee_tasks.status='Overdue'
WHERE employee_tasks.completed_date IS NOT NULL
AND tasks.end_date < employee_tasks.completed_date
");

?>
