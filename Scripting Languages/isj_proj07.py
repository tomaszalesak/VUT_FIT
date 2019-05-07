#!/usr/bin/env python3
#############################
# ISJ practice No. 7        #
# Tomáš Zálešák, xzales13   #
# 3.5.2018                  #
#############################

class TooManyCallsError(Exception):
    """Chyba class"""
    pass


def limit_calls(max_calls=2, error_message_tail="called too often"):
    """Decorator limiting calls"""

    def outer(func):
        """Outer"""

        def wrapper(*args, **kargs):
            """wrapper"""
            wrapper.calls += 1

            if wrapper.calls > max_calls:
                specific_error_message = "function \""
                specific_error_message += str(func.__name__)
                specific_error_message += "\" - "
                specific_error_message += error_message_tail
                raise TooManyCallsError(specific_error_message)

            return func(*args, **kargs)

        wrapper.calls = 0
        return wrapper

    return outer


def ordered_merge(*args, **kwargs):
    """Ordered merge"""

    mylist = list()
    arguments = list()

    # copy kwarg "selector" list to selector
    if kwargs.__contains__("selector"):
        selector = kwargs["selector"]
    else:
        return []

    # append args to arguments list
    for arg in args:
        a = list(arg)
        arguments.append(a)

    for selected in selector:
        mylist.append(arguments[selected][0])
        arguments[selected].pop(0)

    return mylist

class Log(object):
    """My log class"""

    def __enter__(self):
        """Enter"""
        self.output.write("Begin")
        return self

    def __exit__(self, exc_type, exc_val, exc_tb):
        """Exit"""
        self.output.write("\nEnd\n")
        self.output.close()

    def logging(self, l):
        """Logging"""
        self.output.write("\n" + l)

    def __init__(self, fn):
        """Init"""
        self.output = open(fn, "w")

