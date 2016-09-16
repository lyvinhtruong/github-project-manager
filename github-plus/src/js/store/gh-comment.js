"use strict";

var $   = require('jquery')
  , msg = require('config').notInstalledMessage
  , key = 'gh+'
  ;

function GitHubComment() { }

GitHubComment._findAll = function() {
  return $('code:contains(' + key + ')');
};

GitHubComment._findLatest = function() {
  return this._findAll().filter(':last');
};

GitHubComment._getCommentData = function(codeElement) {
  return this._deserialize(codeElement.prev().text());
};

GitHubComment._getLatestCommentData = function() {
  var latest = this._findLatest()
    , commentData = null
    ;

  if (latest.length) {
    commentData = this._getCommentData(latest);
  }

  return commentData;
};

GitHubComment._getContainer = function(el) {
  if ( ! el.parents('div.discussion-bubble').length || el.parents('div.discussion-bubble').length <= 0 ) {
    el.parents('div.js-comment-container').addClass('discussion-bubble');
  }
  return el.parents('div.discussion-bubble');
};

GitHubComment._hideAll = function() {
  this._getContainer(this._findAll()).hide();
};

GitHubComment._serialize = function(data) {
  return JSON.stringify(data);
};

GitHubComment._deserialize = function(data) {
  return JSON.parse(data);
};

GitHubComment._buildComment = function(data) {
  var fullMessage = msg;
  fullMessage += '\n';
  fullMessage += '`' + key + '``' + data + '``' + key + '`';

  return fullMessage;
};

GitHubComment._canEdit = function(latestComment) {
  console.log(latestComment.parents('.comment:first').find('.js-comment-edit-button').length);
  return !!latestComment.parents('.comment:first').find('.js-comment-edit-button').length;
};

GitHubComment._waitForCreateDone = function(cb) {
  var commentCount = $('div.discussion-bubble').length;
  var instance = this;
  
  var interval = setInterval(function() {
    var comments = $('div.discussion-bubble');
    var latest = instance._findLatest();

    if ( latest.length > 0 ) {
      latest.parents('.js-comment-container').addClass('discussion-bubble');
    }

    if (comments.length > commentCount) {
      clearInterval(interval);

      var container = comments.filter(':not(:last)').filter(':last');
      cb(null, container);
    }
    else if (instance.errorOccurred()) {
      clearInterval(interval);
      cb(true);
    }
  }, 200);
};

GitHubComment._waitForUpdateDone = function(latestComment, cb) {
  latestComment.attr('ghplus', true);
  var container = this._getContainer(latestComment);

  var self = this;
  var interval = setInterval(function() {
    if (!self._findLatest().attr('ghplus')) {
      clearInterval(interval);
      cb(null, container);
    }
    else if (self.errorOccurred()) {
      clearInterval(interval);
      cb(true);
    }
  }, 200);
};

GitHubComment.errorOccurred = function() {
  var error = $('.ajax-error-message:visible:not([ghplus])');
  if (error.length) {
    error.attr('ghplus', true);
  }

  return !!error.length;
};

GitHubComment._createNewComment = function(text) {
  this._setTextAndSave($('.write-content').find('textarea'), text);
};

GitHubComment._updateExistingComment = function(commentField, text) {
  var commentId = commentField.parents('div[id^=issuecomment]').attr('id').split('-')[1]
    , commentTextField = $('textarea[data-suggester=issue_comment_' + commentId + '_suggester]')
    ;

   this._setTextAndSave(commentTextField, text);
};

GitHubComment._setTextAndSave = function(textArea, text) {
  var saveButtonSelector = 'button[type=submit]';
  textArea.val(text);

  var formActions = textArea.parent().siblings('.form-actions');
  if (!formActions.length) {
    formActions = $('.form-actions');
    saveButtonSelector = 'button.primary[type=submit]';
  }

  if (formActions.length) {
    var saveButton = formActions.find(saveButtonSelector);
    saveButton.click();
  }
  else {
    console.error('ERROR: Unable to find the correct submit button');
  }
};

GitHubComment.save = function(data, cb) {
  data = this._buildComment(this._serialize(data));

  var latestComment = this._findLatest();

  if (latestComment.length && this._canEdit(latestComment)) {
    this._waitForUpdateDone(latestComment, cb);
    this._updateExistingComment(latestComment, data);
  }
  else {
    console.log('Run GitHubComment.save()');
    this._waitForCreateDone(cb);
    this._createNewComment(data);
  }
};

GitHubComment.load = function() {
  this._hideAll();
  return this._getLatestCommentData();
};

module.exports = GitHubComment;
