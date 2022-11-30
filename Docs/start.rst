**Opening a sample dataset**
-------------------------------
A Demo dataset is present in the QUINT-DEmo collab:https://wiki.ebrains.eu/bin/view/Collabs/quint-demo/

Load the file by clicking on the file:demo_mouse_data_start.waln

Instructions about the operations are found both under "Documentation" on the upper right and inside the application by pressing the question mark.
In this ReadtheDocs, go to the next page for illustrated alignment instructions.

You can see the result of a finished anchoring by choosing the file: demo_mouse_data.waln


**Ingest your images with the Image Service app**
----------------------------------------------------




**Opening of your own dataset**
-----------------------------------

After you have uploaded your images to the collab bucket and ingested your images with the Image service app, you will find generated DZI chunks in the bucket.
These DZI files are used by WebAlign.

1. Start a new registration by pressing "create new series", the UI will ask you for the name of your series. E.g. my-registration

2. The name of the present bucket is prefilled but you if your ingested data are located in another collab, you can type the name of that collab

3. Choose the target atlas (WHSv4 for Rat and CCFv3_2017 for Mouse).

4. WebAlign will search for DZI files and list those found.

5. Press "create". The main window will now display WebAlign. This step can take some time.

.. image:: images/media/image2.png
  :width: 6.30139in
  :height: 3.54662in
