const downloadStudentTemplateButton = document.getElementById(
  "downloadStudentTemplateButton"
);

if (downloadStudentTemplateButton) {
  downloadStudentTemplateButton.addEventListener("click", (event) => {
    event.preventDefault(); // Prevent the default link behavior

    // Trigger download for item_csv_column_descriptions.pdf
    const pdfLink = document.createElement("a");
    pdfLink.href = "../resources/student_csv_column_descriptions.pdf";
    pdfLink.download = "student_csv_column_descriptions.pdf";
    document.body.appendChild(pdfLink);
    pdfLink.click();
    document.body.removeChild(pdfLink);

    // Trigger download for item_csv_template.csv after a short delay
    setTimeout(() => {
      const csvLink = document.createElement("a");
      csvLink.href = "../resources/add_student_template.csv";
      csvLink.download = "add_student_template.csv";
      document.body.appendChild(csvLink);
      csvLink.click();
      document.body.removeChild(csvLink);
    }, 250); // Adjust the delay (in milliseconds) if needed
  });
}

const uploadStudentCSVButton = document.getElementById(
  "uploadStudentCSVButton"
);
const studentCSVFile = document.getElementById("studentCSVFile");

uploadStudentCSVButton.addEventListener("click", () => {
  studentCSVFile.click();
});

// Handle file selection
studentCSVFile.addEventListener("change", (event) => {
  const file = event.target.files[0];
  if (!file) return;

  Papa.parse(file, {
    header: true,
    skipEmptyLines: true,

    complete: function (results) {
      let data = results.data;

      // Remove trailing empty row if present
      if (
        data.length > 0 &&
        Object.values(data[data.length - 1]).every((v) => v === "")
      ) {
        data.pop();
      }

      // Function to fetch department ID from department name
      const getDepartmentId = (departmentName) => {
        return fetch(
          `../php/get_department_id.php?department_name=${encodeURIComponent(
            departmentName
          )}`
        )
          .then((response) => response.json())
          .then((deptData) => {
            if (deptData.success) {
              return deptData.department_id;
            } else {
              console.error(
                `Department "${departmentName}" not found:`,
                deptData.message
              );
              return null;
            }
          })
          .catch((error) => {
            console.error("Error fetching department ID:", error);
            return null;
          });
      };

      // Prepare all student data with resolved department IDs
      const processedData = Promise.all(
        data.map((row) => {
          const studentData = {
            student_id: row["Student ID"] ?? null,
            first_name: row["First Name"] ?? null,
            middle_name: row["Middle Name"] ?? null,
            last_name: row["Last Name"] ?? null,
            suffix: row["Suffix"] ?? null,
            gender: row["Gender"] ?? null,
            section: row["Section"] ?? null,
            contact_number: row["Contact Number"] ?? null,
            email: row["Email"] ?? null,
            stud_address: row["Address"] ?? null,
            department_id: null,
          };

          return getDepartmentId(row["Department"]).then((deptId) => {
            studentData.department_id = deptId;
            return studentData;
          });
        })
      );

      // Submit the processed data
      processedData.then((finalData) => {
        fetch("../php/add_students_from_csv.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify(finalData),
        })
          .then((response) => response.json())
          .then((response) => {
            alert(response.message);
            if (response.success) {
              studentCSVFile.value = "";
            }
          })
          .catch((error) => {
            console.error("Fetch error:", error);
            alert(
              "Student information upload failed. Check for duplicate IDs and missing data."
            );
          });
      });
    },

    error: function (error) {
      console.error("Error parsing CSV:", error.message);
      alert("Error parsing CSV file: " + error.message);
    },
  });
});
