
//��ʼ���������¼�
$(function() {
  
  //����ť���ּ�����ɫ���������ȥ����ɫ
  if (is_showdoc_online()) {
    set_text_color( "runapi" , "red");
  };
  
  /*����Ŀ¼*/
  secondCatList();

  function secondCatList() {
    var default_second_cat_id = $("#default_second_cat_id").val();
    var item_id = $("#item_id").val();
    $.post(
      "?s=home/catalog/secondCatList",
      {
        "item_id": item_id,
      },
      function(data) {
        $("#cat_id").html('<OPTION value="0">'+lang["none"]+'</OPTION>');
        if (data.error_code == 0) {
          json = data.data;
          for (var i = 0; i < json.length; i++) {
            cat_html = '<OPTION value="' + json[i].cat_id + '" ';
            if (default_second_cat_id == json[i].cat_id) {
              cat_html += ' selected ';
            }

            cat_html += ' ">' + json[i].cat_name + '</OPTION>';
            $("#cat_id").append(cat_html);
          };
          getChildCatList();
        };

      },
      "json"

    );
  }

  function getChildCatList() {
    var cat_id = $("#cat_id").val();
    var default_child_cat_id = $("#default_child_cat_id").val();
    $.post(
      "?s=home/catalog/childCatList", {
        "cat_id": cat_id
      },
      function(data) {
        $("#parent_cat_id").html('<OPTION value="0">'+lang["none"]+'</OPTION>');
        if (data.error_code == 0) {
          json = data.data;
          for (var i = 0; i < json.length; i++) {
            cat_html = '<OPTION value="' + json[i].cat_id + '" ';
            if (default_child_cat_id == json[i].cat_id) {
              cat_html += ' selected ';
            }

            cat_html += ' ">' + json[i].cat_name + '</OPTION>';
            $("#parent_cat_id").append(cat_html);
          };
        }else{
        }

      },
      "json"

    );
  }
  //�����Ƿ�ѡ����Ŀ¼�����ѡ���ˣ������̨�ж��Ƿ���Ŀ¼
  $("#cat_id").change(function(){
    getChildCatList();
  });

  var fileInput  = $('#editormd-image-file');
  
  /**
  find("[type=\"text\"]").val("");
			dialog.find("[type=\"file\"]").val("");
			dialog.find("[data-link]").val("http://");
  */
  var settings = {
    imageFormats: ["jpg", "jpeg", "gif", "png", "bmp", "webp", "JPG", "JPEG", "GIF", "PNG", "BMP", "WEBP"],
    imageUploadURL: "?s=home/file/uploadImg"
  };
  fileInput.bind("change", function() {
    var fileName  = fileInput.val();
    var isImage   = new RegExp("(\\.(" + settings.imageFormats.join("|") + "))$"); // /(\.(webp|jpg|jpeg|gif|bmp|png))$/
    
    if (fileName === "")
    {
      alert(imageLang.uploadFileEmpty);
      return false;
    }
    
    if (!isImage.test(fileName))
    {
      alert(imageLang.formatNotAllowed + settings.imageFormats.join(", "));
                  
      return false;
    }

    //loading(true); //���ؽ���ͼ������

    var submitHandler = function() {

        var uploadIframe = document.getElementById("editormd-image-iframe");

        uploadIframe.onload = function() {
            
            //loading(false); //���ؽ���ͼ��������

            var body = (uploadIframe.contentWindow ? uploadIframe.contentWindow : uploadIframe.contentDocument).document.body;
            var json = (body.innerText) ? body.innerText : ( (body.textContent) ? body.textContent : null);

            json = (typeof JSON.parse !== "undefined") ? JSON.parse(json) : eval("(" + json + ")");

            if (json.success === 1)
            {
                $("#file_url").val(json.url);
                $('#preview').attr({"src":json.url});
            }
            else
            {
                alert(json.message);
            }

            return false;
        };
      };

    $("#submit").bind("click", submitHandler).trigger("click");;

  });
  
  /*����*/
  var saving = false;
  $("#save").click(function() {
    if (saving) return false;
    var file_id = $("#file_id").val();
    var item_id = $("#item_id").val();
    var file_title = $("#file_title").val();
    var file_comments = $("#file_comments").val();
    var file_url = $("#file_url").val();
    var item_id = $("#item_id").val();
    var s_number = $("#s_number").val();
    var cat_id = $("#cat_id").val();
    var parent_cat_id = $("#parent_cat_id").val();
    if (parent_cat_id > 0 ) {
      cat_id = parent_cat_id ;
    };
    
    saving = true;
    $.post(
      "?s=home/file/save", {
        "file_id": file_id,
        "cat_id": cat_id,
        "s_number": s_number,
        "file_url": file_url,
        "file_title": file_title,
        "file_comments": file_comments,
        "item_id": item_id
      },
      function(data) {
        if (data.error_code == 0) {
          $.bootstrapGrowl(lang["save_success"]);
          window.location.href = "?s=home/item/show&file_id=" + data.data.file_id + "&item_id=" + item_id;
        } else {
          $.bootstrapGrowl(lang["save_fail"]);

        }
        saving = false;
      },
      'json'
    )
  });

  //�����ļ�ע��
  $("#add-file-comments").click(function(){
    var file_comments =prompt(lang["add_page_comments_msg"],"");
    if (file_comments!=null && file_comments!="")
    {
        $("#file_comments").val(file_comments);
        $("#save").click();
    }
    $("#save-btn-group").removeClass("open");
    return false;
  });
  
});