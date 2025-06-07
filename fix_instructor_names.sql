-- Move email from instructor_name to email column where it was incorrectly stored
UPDATE instructors 
SET email = instructor_name,
    instructor_name = NULL
WHERE instructor_name LIKE '%@%';

-- Update instructor names for existing records
UPDATE instructors
SET instructor_name = 'Fritz Aseo'
WHERE instructor_id = '2025-0000';

UPDATE instructors
SET instructor_name = 'Pol'
WHERE instructor_id = '2025-0001';

UPDATE instructors
SET instructor_name = 'Aseo'
WHERE instructor_id = '2025-0002'; 