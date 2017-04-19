
$(function() {

  //给按钮文字加上颜色，点击后则去掉颜色
  if (is_showdoc_online()) {
    set_text_color( "runapi" , "red");
  };
  
  /*加载目录*/
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
  //监听是否选择了目录。如果选择了，则跟后台判断是否还子目录
  $("#cat_id").change(function(){
    getChildCatList();
  });

  // 如果是新增页面，则光标为标题文本框
  if (location.href.indexOf('type=new') !== -1) {
    setTimeout(function() {
      $('#file_title').focus();
    }, 1000);
  }
  
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

    //loading(true); //加载进度图，锁屏

    var submitHandler = function() {

        var uploadIframe = document.getElementById("editormd-image-iframe");

        uploadIframe.onload = function() {
            
            //loading(false); //加载进度图，解锁屏

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

  /*保存*/
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

});

function closeDiv(target)
{
	$(target).hide();
}

function Change(data)
{
	var level_str="- ";
	if(arguments.length>1)
	{
		var level;
		arguments[1]>0?level=arguments[1]:level=1;
		for(var i=0;i<level;i++)
		{
			level_str+="- ";
		}
	}
	
	for(var key in data)
	{
		var value = data[key];
		var type = typeof(value);
		if(type == "object")
		{
			json_table_data+='| '+level_str+key+' |'+type+'  | '+lang["none"]+' |\n';
			if(value instanceof Array)
			{
				var j=level+1;
				Change(value[0],j);
				continue;
			}
			//else
			//{
				Change(value,level);
			//}
			
		}
		else
		{
			json_table_data+='| '+key+' | '+type+'| '+lang["none"]+' |\n';
		}
	}
}

$("#add-file-comments").click(function(){
  var file_comments =prompt(lang["add_file_comments_msg"],"");
  if (file_comments!=null && file_comments!="")
    {
        $("#file_comments").val(file_comments);
        $("#save").click();
    }
    $("#save-btn-group").removeClass("open");
    return false;
});

