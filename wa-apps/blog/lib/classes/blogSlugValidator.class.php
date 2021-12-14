<?php

class blogSlugValidator extends waStringValidator
{
    const SUBJECT_BLOG = 'blog';
    const SUBJECT_POST = 'post';

    const ERROR_REQUIRED = 'required';
    const ERROR_URL_IN_USE = 'url_in_use';
    const ERROR_INVALID = 'invalid';

    private $subject = self::SUBJECT_BLOG;

    protected $options = array(
        self::ERROR_REQUIRED => true,
        self::ERROR_URL_IN_USE => true,
        self::ERROR_INVALID => true,
        'id' => null		// use in isInUse method
    );

    protected $errors_map = array(
        self::ERROR_REQUIRED => false,
        self::ERROR_URL_IN_USE => false,
        self::ERROR_INVALID => false
    );

    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    public function isValid($value)
    {
        $this->clearErrors();

        $errors = array(
            self::ERROR_REQUIRED => array(
                'blog' => _w('Blog address must not be empty.'),
                'post' => _w('Post address must not be empty.'),
            ),
            self::ERROR_URL_IN_USE => array(
                'blog' => _w('Blog URL is in use. Please enter another URL'),
                'post' => _w('This post address is already in use. Please enter another address.'),
            ),
            self::ERROR_INVALID => array(
                'blog' => _w('Blog address is invalid.'),
                'post' => _w('Post address is invalid.'),
            )
        );

        if ($this->getOption(self::ERROR_REQUIRED, false) && $this->isEmpty($value)) {
            $this->setError($errors[self::ERROR_REQUIRED][$this->subject]);
            $this->errors_map[self::ERROR_REQUIRED] = true;
        }

        if ($this->getOption(self::ERROR_URL_IN_USE, false) && $this->isInUse($value)) {
            $this->setError($errors[self::ERROR_URL_IN_USE][$this->subject]);
            $this->errors_map[self::ERROR_URL_IN_USE] = true;
        }
        if (!preg_match('/^[a-zA-Z0-9_-]*$/', $value)) {
            $this->setError($errors[self::ERROR_INVALID][$this->subject]);
            $this->errors_map[self::ERROR_INVALID] = true;
        }

        return !$this->getErrors();
    }

    public function isInUse($value)
    {
        if ($this->subject == self::SUBJECT_BLOG) {
            $model = new blogBlogModel();
        } else {
            $model = new blogPostModel();
        }

        $cond = $this->options['id'] ? 'url = :url AND id != i:id' : 'url = :url';

        return $model
            ->select('id')
            ->where($cond, array('url' => $value, 'id' => $this->options['id']))
            ->limit(1)
            ->fetch();
    }

    public function isError($error_code)
    {
        return $this->errors_map[$error_code];
    }

}
