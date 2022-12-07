**Opening a sample dataset**
-------------------------------
A Demo dataset is present in the QUINT-Demo collab: https://wiki.ebrains.eu/bin/view/Collabs/quint-demo/

Load the file by clicking on the file: "demo_mouse_data_start.waln"

Instructions about the operations are found both under "Documentation" on the upper right and inside the application by pressing the question mark.
In this ReadtheDocs, go to the next page for illustrated alignment instructions.

You can see the result of a finished anchoring by choosing the file: "demo_mouse_data.waln"


**Work with your own images**
----------------------------------------------------
**1. Prepare your images before upload by naming them according this naming convention:**

The ID should be unique to the particular brain section and in the format sXXX, with XXX representing the section number. The section number should reflect the serial order and spacing of the sections (e.g., s002, s006, s010 for every 4th section starting with section 2).

Example: tg2345_MMSH_s001_segmentation.png

- Upload the images you want to work with into the bucket of your collab using the Data proxy (press on "Bucket")

**2. Start the Image service UI from your webapp**

*2a. Fill in the requested information:*

- type or paste the URL of the dataset folder containing the images. Note! the name of the collab supports only hyphens.The URL can be fetched from the    Bucket with right-click and choose "Gey API url".

:note::
 E.g. "https[]://data-proxy.ebrains.eu/api/v1/public/buckets/name-of-bucket?prefix=name_of_bucket"

- you can filter the data using RegEx expressions. E.g. name_of_the_data_folder\/.*\.jpg$ (for filtering jpeg files only).

- If your URL is valid, you will see the list of files by pressing "yes". Paste the name of the file you want to select. You can go back to the previous step  by pressing "back"

- allow the image service to access your bucket

- Store your results: Choose "Use current collab".  If you have a lot of data and in order not to overload the Bucket of your current collab, you can choose the option "create a new collab". Give a name to your Bucket as well as a name for the collab.

- Customize your ingestion:

       - give a name to your ingestion in the "description of ingestion" field.

       - Individual 2D files can be ingested into two formats: Deep zoom format(DZI) or Neuroglancer (NG); whereas 3D volumes (nifti format) are only ingested into NG format. Consult image service documentation for more details.

       - For obtaining chunks in DZI format  (compatible with WebAlign), choose "2D" and "not stack of Images". 
       - Click "preview" in order to preview your task.
       It could look like this:
       {"definition":{"type":"ingest","url":"*https[]://data-proxy.ebrains.eu/api/v1/public/buckets/quint-demo?prefix=quint-demo*","two_d":true,"runtime_limit":"12h","queue":"normal","ingestion_parameters":{"is_stack":false,"type":"image","data_type":"uint8","output_format":"jpg"}},"description":"test"}

*2b. Click "create task" to launch your ingestion*

*2c. Checking results in the Main UI page*

- Press "Task" in the higher right corner of the window.

- When creating the task, a red banner is displayed in the "Create Task" view if something goes wrong for instance.

- Otherwise, you will be redirected to the tasks list where the created task is selected.

Note! A task will be first "Queued" then "Running"; "Stagingout"and ultimately "Successful" or "Failed"

Refresh your browser in order to check the status of your task.

When "successful", your chunks have been created.

In case of problem contact support at support@ebrains.eu


**Opening of your own dataset in WebAlign**
-----------------------------------

After you have uploaded your images to the collab bucket and ingested your images with the Image service app, you will find generated DZI chunks in the bucket.
These DZI files are used by WebAlign.

1. Start a new registration by pressing "create new series", the UI will ask you for the name of your series. E.g. my-registration

2. The name of the present bucket is prefilled but you if your ingested data are located in another collab, you can type the name of that collab

3. Choose the target atlas (WHSv4 for Rat and CCFv3_2017 for Mouse).

4. WebAlign will search for DZI files and list those found.

5. Press "create". The main window will now display WebAlign. This step can take some time.
Your file is saved in the Bucket as a .waln file


.. image:: images/image2.png
  :width: 3.30139in
  :height: 3.54662in
  
  
  
  
