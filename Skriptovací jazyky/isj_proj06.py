#!/usr/bin/env python3
#############################
# ISJ practice No. 6        #
# Tomáš Zálešák, xzales13   #
# 29.3.2018                 #
#############################


def first_nonrepeating(s):
    """Returns the first non-repeated char in s string, otherwise None"""

    if not isinstance(s, str):
        return None

    if s == '\t' or s == '\n' or s == ' ':
        return None

    my_list = []
    my_dict = {}

    for x in s:
        if x in my_dict:
            my_dict[x] += 1;
        else:
            my_dict[x] = 1
            my_list.append(x)

    for x in my_list:
        if my_dict[x] == 1:
            return x

    return None

