import {Controller} from "stimulus"
const Toastify = require("toastify-js")
export default class extends Controller {
    static targets = ["friendNotification", "friendsRequestArea"]
    initialize() {
        if (!this.notificationsCount) {
            this.notificationsCount = this.friendRequested
        }
    }


    /* events and methods */
    parseNotification(event) {
        const notification = event.detail
        console.log(notification)
        switch (notification.type) {
            case `App\\Notifications\\FriendRequested`:
                this.friendRequestedNotification(notification)
                break
            case `App\\Notifications\\FriendRequestCanceled`:
                this.friendRequestCanceled(notification)
                break     
            case `App\\Notifications\\FriendAccepted`:
                this.friendRequestAccepted(notification)
                break        
            default:
                break
        }
    }
    newToast(options) {
        const toastOptions = Object.assign({}, {
            duration: 700000,
            close: true,
            gravity: "bottom",
            positionLeft: true,
        }, options)
        if (document.visibilityState === "visible") {
            Toastify(toastOptions).showToast()
        } else {
            window.addEventListener("focus", () => {
                Toastify(toastOptions).showToast()
            }, {once: true})
        }
        
    }
    friendRequestAccepted(notification) {
        this.friendRequested--
        this.notificationsCount--
        if (this.hasFriendsRequestAreaTarget) {
            this.friendsRequestAreaTarget.dispatchEvent(new Event("rebuild"))
        }        
        console.log("send notification -> friend request accepted")
        this.newToast({
            text: notification.message,
            avatar: notification.friend.avatar,
            classes: "link",
        })
    }
    friendRequestedNotification(notification) {
        this.friendRequested++
        this.notificationsCount++
        // kick-off reloading friendRequestArea
        if (this.hasFriendsRequestAreaTarget) {
            this.friendsRequestAreaTarget.dispatchEvent(new Event("rebuild"))
        }
        console.log("send notification -> friend requested")
        this.newToast({
            text: notification.message,
            avatar: notification.friend.avatar,
        })
    }
    friendRequestCanceled(notification) {
        this.friendRequested--
        this.notificationsCount--
        if (this.hasFriendsRequestAreaTarget) {
            this.friendsRequestAreaTarget.dispatchEvent(new Event("rebuild"))
        }
        console.log("send notification -> friend canceled")
    }
    /* magic get/set */
    get friendRequested() {
        return parseInt(this.friendNotificationTarget.innerHTML)
    }
    set friendRequested(value) {
        if (value > 0) {
            this.friendNotificationTarget.style.display = "block"
        } else if (0 <= value) {
            this.friendNotificationTarget.style.display = "none"
        }
        this.friendNotificationTarget.innerHTML = value
    }
    get notificationsCount() {
        return this.data.get("notificationsCount")
    }
    set notificationsCount(value) {
        if (value > 0) {
            window.document.title = `Chatt (${value})`
        } else {
            window.document.title = `Chatt`
        }
        this.data.set("notificationsCount", value)
    }
}